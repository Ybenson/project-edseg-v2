<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'user_compta')) {
    header("Location: index.php");
    exit();
}

// Check if payment ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$paymentId = $_GET['id'];
$payment = getPaymentById($paymentId);

// If payment not found, redirect to index
if (!$payment) {
    header("Location: index.php");
    exit();
}

// Get user information
$user = getUserById($_SESSION['user_id']);
$isAdmin = $user['role'] === 'admin';
$isUserCompta = $user['role'] === 'user_compta';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Paiement - Système de Gestion</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Détails du Paiement</h1>
            <div class="navigation">
                <a href="index.php" class="btn">Retour à l'accueil</a>
                <?php if ($isUserCompta): ?>
                <a href="add_payment.php?student_id=<?php echo $payment['student_id']; ?>" class="btn">Paiements de l'étudiant</a>
                <?php else: ?>
                <a href="view_student.php?id=<?php echo $payment['student_id']; ?>" class="btn">Détails de l'étudiant</a>
                <?php endif; ?>
                <a href="all_payments.php" class="btn">Tous les paiements</a>
                <?php if ($isAdmin && $payment['status'] === 'en_attente'): ?>
                <a href="validate_payment.php?id=<?php echo $paymentId; ?>" class="btn btn-primary">Valider ce paiement</a>
                <?php endif; ?>
            </div>
        </header>

        <main>
            <div class="payment-details">
                <div class="payment-header">
                    <h2>Paiement #<?php echo $paymentId; ?></h2>
                    <div class="payment-status <?php echo $payment['status']; ?>">
                        <?php 
                            if ($payment['status'] === 'en_attente') echo 'En attente';
                            elseif ($payment['status'] === 'approuve') echo 'Approuvé';
                            else echo 'Rejeté';
                        ?>
                    </div>
                </div>
                
                <div class="details-section">
                    <h3>Informations de paiement</h3>
                    <div class="details-grid">
                        <div class="detail-item">
                            <div class="detail-label">Montant</div>
                            <div class="detail-value"><?php echo htmlspecialchars(number_format($payment['montant'], 2)); ?> HTG</div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Référence</div>
                            <div class="detail-value"><?php echo htmlspecialchars($payment['reference']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Date du paiement</div>
                            <div class="detail-value"><?php echo htmlspecialchars($payment['date_paiement']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="details-section">
                    <h3>Informations de l'étudiant</h3>
                    <div class="details-grid">
                        <div class="detail-item">
                            <div class="detail-label">Nom</div>
                            <div class="detail-value"><?php echo htmlspecialchars($payment['student_nom'] . ' ' . $payment['student_prenom']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">No d'ordre</div>
                            <div class="detail-value"><?php echo htmlspecialchars($payment['no_ordre']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="details-section">
                    <h3>Informations de traitement</h3>
                    <div class="details-grid">
                        <div class="detail-item">
                            <div class="detail-label">Créé par</div>
                            <div class="detail-value"><?php echo htmlspecialchars($payment['created_by_username']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Date de création</div>
                            <div class="detail-value"><?php echo htmlspecialchars($payment['created_at']); ?></div>
                        </div>
                        
                        <?php if ($payment['status'] !== 'en_attente'): ?>
                        <div class="detail-item">
                            <div class="detail-label">Validé par</div>
                            <div class="detail-value"><?php echo htmlspecialchars($payment['updated_by_username']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Date de validation</div>
                            <div class="detail-value"><?php echo htmlspecialchars($payment['updated_at']); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($payment['commentaire'])): ?>
                <div class="details-section">
                    <h3>Commentaire</h3>
                    <div class="commentaire-box">
                        <?php echo nl2br(htmlspecialchars($payment['commentaire'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($isUserCompta): ?>
                <div class="form-actions">
                    <a href="add_payment.php?student_id=<?php echo $payment['student_id']; ?>" class="btn btn-payment">Ajouter un nouveau paiement</a>
                </div>
                <?php endif; ?>
            </div>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Système de Gestion des Étudiants. Tous droits réservés.</p>
        </footer>
    </div>
</body>
</html>