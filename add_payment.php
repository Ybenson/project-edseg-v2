<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in and is comptabilité
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user_compta') {
    header("Location: index.php");
    exit();
}

// Check if student_id is provided
if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    header("Location: index.php");
    exit();
}

$studentId = $_GET['student_id'];
$student = getStudentById($studentId);

// If student not found, redirect to index
if (!$student) {
    header("Location: index.php");
    exit();
}

// Récupérer le montant total payé et le montant restant
$totalPaid = getTotalPaidAmount($studentId);
$remainingAmount = getRemainingAmount($studentId);

$success = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $montant = isset($_POST['montant']) ? floatval($_POST['montant']) : 0;
    $reference = $_POST['reference'] ?? '';
    $datePaiement = $_POST['date_paiement'] ?? date('Y-m-d');
    
    if ($montant <= 0) {
        $error = "Le montant doit être supérieur à zéro.";
    } elseif (empty($reference)) {
        $error = "La référence du paiement est obligatoire.";
    } elseif (empty($datePaiement)) {
        $error = "La date du paiement est obligatoire.";
    } else {
        $paymentId = addPayment($studentId, $montant, $reference, $datePaiement, $_SESSION['user_id']);
        
        if ($paymentId) {
            $success = "Paiement enregistré avec succès. Il est en attente de validation par un administrateur.";
            // Mettre à jour les montants après l'ajout du paiement
            $totalPaid = getTotalPaidAmount($studentId);
            $remainingAmount = getRemainingAmount($studentId);
        } else {
            $error = "Erreur lors de l'enregistrement du paiement.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Paiement - Système de Gestion</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Ajouter un Paiement</h1>
            <div class="navigation">
                <a href="index.php" class="btn">Retour à l'accueil</a>
            </div>
        </header>

        <main>
            <div class="form-container">
                <div class="student-info">
                    <h2>Étudiant: <?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenom']); ?></h2>
                    <p>No d'ordre: <?php echo htmlspecialchars($student['no_ordre']); ?></p>
                    <div class="payment-summary">
                        <p><strong>Montant total payé:</strong> <?php echo number_format($totalPaid, 2); ?> HTG</p>
                        <p><strong>Montant requis:</strong> 6000,00 HTG</p>
                        <p><strong>Montant restant à payer:</strong> <?php echo number_format($remainingAmount, 2); ?> HTG</p>
                        <p><strong>Statut:</strong> 
                            <span class="payment-status <?php echo $remainingAmount <= 0 ? 'paid' : 'unpaid'; ?>">
                                <?php echo $remainingAmount <= 0 ? 'Payé' : 'Non payé'; ?>
                            </span>
                        </p>
                    </div>
                </div>
                
                <?php if (!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="add_payment.php?student_id=<?php echo $studentId; ?>">
                    <div class="form-section">
                        <h3>Informations de paiement</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="montant">Montant (HTG) *</label>
                                <input type="number" id="montant" name="montant" step="0.01" min="0.01" value="<?php echo $remainingAmount > 0 ? $remainingAmount : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="reference">Référence du paiement *</label>
                                <input type="text" id="reference" name="reference" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_paiement">Date du paiement *</label>
                                <input type="date" id="date_paiement" name="date_paiement" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Enregistrer le paiement</button>
                    </div>
                </form>
                
                <!-- Afficher l'historique des paiements pour cet étudiant -->
                <div class="details-section">
                    <h3>Historique des Paiements</h3>
                    <?php 
                    $payments = getStudentPayments($studentId);
                    if (empty($payments)): 
                    ?>
                        <p>Aucun paiement enregistré pour cet étudiant.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Montant</th>
                                        <th>Référence</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['date_paiement']); ?></td>
                                        <td><?php echo htmlspecialchars(number_format($payment['montant'], 2)); ?> HTG</td>
                                        <td><?php echo htmlspecialchars($payment['reference']); ?></td>
                                        <td class="payment-status <?php echo $payment['status']; ?>">
                                            <?php 
                                                if ($payment['status'] === 'en_attente') echo 'En attente';
                                                elseif ($payment['status'] === 'approuve') echo 'Approuvé';
                                                else echo 'Rejeté';
                                            ?>
                                        </td>
                                        <td class="actions">
                                            <a href="view_payment.php?id=<?php echo $payment['id']; ?>" class="btn-small">Détails</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Système de Gestion des Étudiants. Tous droits réservés.</p>
        </footer>
    </div>
</body>
</html>