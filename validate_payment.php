<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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

// If payment not found or not pending, redirect to index
if (!$payment || $payment['status'] !== 'en_attente') {
    header("Location: index.php");
    exit();
}

$student = getStudentById($payment['student_id']);

$success = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $commentaire = $_POST['commentaire'] ?? '';
    
    if (empty($status) || !in_array($status, ['approuve', 'rejete'])) {
        $error = "Veuillez sélectionner un statut valide.";
    } else {
        $result = updatePaymentStatus($paymentId, $status, $commentaire, $_SESSION['user_id']);
        
        if ($result) {
            $statusText = ($status === 'approuve') ? 'approuvé' : 'rejeté';
            $success = "Le paiement a été $statusText avec succès.";
            
            // Refresh payment data
            $payment = getPaymentById($paymentId);
        } else {
            $error = "Erreur lors de la mise à jour du statut du paiement.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valider un Paiement - Système de Gestion</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Valider un Paiement</h1>
            <div class="navigation">
                <a href="index.php" class="btn">Retour à l'accueil</a>
                <a href="view_student.php?id=<?php echo $payment['student_id']; ?>" class="btn">Détails de l'étudiant</a>
                <a href="pending_payments.php" class="btn">Paiements en attente</a>
            </div>
        </header>

        <main>
            <div class="form-container">
                <div class="payment-info">
                    <h2>Détails du paiement</h2>
                    <div class="details-grid">
                        <div class="detail-item">
                            <div class="detail-label">Étudiant</div>
                            <div class="detail-value"><?php echo htmlspecialchars($payment['student_nom'] . ' ' . $payment['student_prenom']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">No d'ordre</div>
                            <div class="detail-value"><?php echo htmlspecialchars($payment['no_ordre']); ?></div>
                        </div>
                        
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
                        
                        <div class="detail-item">
                            <div class="detail-label">Créé par</div>
                            <div class="detail-value"><?php echo htmlspecialchars($payment['created_by_username']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Date de création</div>
                            <div class="detail-value"><?php echo htmlspecialchars($payment['created_at']); ?></div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($payment['status'] === 'en_attente'): ?>
                <form method="post" action="validate_payment.php?id=<?php echo $paymentId; ?>">
                    <div class="form-section">
                        <h3>Validation du paiement</h3>
                        
                        <div class="form-group">
                            <label>Statut *</label>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="status" value="approuve" required> Approuver
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="status" value="rejete" required> Rejeter
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="commentaire">Commentaire</label>
                            <textarea id="commentaire" name="commentaire" rows="4"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Valider</button>
                    </div>
                </form>
                <?php else: ?>
                <div class="payment-status-info">
                    <h3>Statut du paiement</h3>
                    <div class="status-box <?php echo $payment['status']; ?>">
                        <?php 
                            if ($payment['status'] === 'approuve') echo 'Approuvé';
                            else echo 'Rejeté';
                        ?>
                    </div>
                    
                    <?php if (!empty($payment['commentaire'])): ?>
                    <div class="commentaire">
                        <h4>Commentaire</h4>
                        <p><?php echo nl2br(htmlspecialchars($payment['commentaire'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="validation-info">
                        <p>Validé par: <?php echo htmlspecialchars($payment['updated_by_username']); ?></p>
                        <p>Date de validation: <?php echo htmlspecialchars($payment['updated_at']); ?></p>
                    </div>
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