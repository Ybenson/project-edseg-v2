<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Get all pending payments
$pendingPayments = getPendingPayments();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiements en Attente - Système de Gestion</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Paiements en Attente</h1>
            <div class="navigation">
                <a href="index.php" class="btn">Retour à l'accueil</a>
                <a href="all_payments.php" class="btn">Tous les paiements</a>
            </div>
        </header>

        <main>
            <div class="data-section">
                <h2>Liste des Paiements en Attente</h2>
                
                <?php if (empty($pendingPayments)): ?>
                    <p>Aucun paiement en attente de validation.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Étudiant</th>
                                    <th>No d'ordre</th>
                                    <th>Montant</th>
                                    <th>Référence</th>
                                    <th>Créé par</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingPayments as $payment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payment['date_paiement']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['student_nom'] . ' ' . $payment['student_prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['no_ordre']); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($payment['montant'], 2)); ?> HTG</td>
                                    <td><?php echo htmlspecialchars($payment['reference']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['created_by_username']); ?></td>
                                    <td class="actions">
                                        <a href="validate_payment.php?id=<?php echo $payment['id']; ?>" class="btn-small btn-primary">Valider</a>
                                        <a href="view_student.php?id=<?php echo $payment['student_id']; ?>" class="btn-small">Voir l'étudiant</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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