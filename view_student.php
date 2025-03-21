<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];
$student = getStudentById($id);

// If student not found, redirect to index
if (!$student) {
    header("Location: index.php");
    exit();
}

// Get user information
$user = getUserById($_SESSION['user_id']);
$isAdmin = $user['role'] === 'admin';
$isUserAdmin = $user['role'] === 'user_admin';
$isUserCompta = $user['role'] === 'user_compta';

// Redirect comptabilité users to payment page
if ($isUserCompta) {
    header("Location: add_payment.php?student_id=" . $id);
    exit();
}

// Check if user_admin has access (only if payment is approved)
if ($isUserAdmin && !hasApprovedPayment($id)) {
    // Récupérer le montant total payé et le montant restant
    $totalPaid = getTotalPaidAmount($id);
    $remainingAmount = getRemainingAmount($id);
    
    // Afficher un message d'erreur et rediriger vers la page d'accueil
    $_SESSION['error_message'] = "Les résultats de cet étudiant ne sont pas disponibles. Paiement requis: " . number_format($totalPaid, 2) . " HTG / 6000 HTG. Montant restant à payer: " . number_format($remainingAmount, 2) . " HTG.";
    header("Location: index.php");
    exit();
}

// Get student payments
$payments = getStudentPayments($id);

// Generate PDF or Word document
$documentType = isset($_GET['format']) ? $_GET['format'] : '';
if ($documentType === 'pdf' || $documentType === 'word') {
    generateDocument($student, $documentType);
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'Étudiant - Système de Gestion</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Détails de l'Étudiant</h1>
            <div class="navigation">
                <a href="index.php" class="btn">Retour à l'accueil</a>
                <?php if ($isAdmin): ?>
                <a href="edit_student.php?id=<?php echo $id; ?>" class="btn">Modifier</a>
                <a href="delete_student.php?id=<?php echo $id; ?>" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet étudiant?');">Supprimer</a>
                <?php endif; ?>
                <?php if ($isUserCompta): ?>
                <a href="add_payment.php?student_id=<?php echo $id; ?>" class="btn btn-payment">Ajouter un Paiement</a>
                <?php endif; ?>
                <?php if ($isAdmin && $student['verdict'] === 'Reprise'): ?>
                <a href="add_reprise.php?id=<?php echo $id; ?>" class="btn">Gérer les reprises</a>
                <?php endif; ?>
            </div>
        </header>

        <main>
            <div class="student-details">
                <div class="student-header">
                    <h2><?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenom']); ?></h2>
                    <div class="verdict <?php echo strtolower($student['verdict']); ?>">
                        <?php echo htmlspecialchars($student['verdict']); ?>
                    </div>
                </div>
                
                <div class="export-options">
                    <h3>Exporter les données</h3> <div class="export-buttons">
                        <a href="view_student.php?id=<?php echo $id; ?>&format=pdf" class="btn btn-export">Exporter en PDF</a>
                        <a href="view_student.php?id=<?php echo $id; ?>&format=word" class="btn btn-export">Exporter en Word</a>
                    </div>
                </div>
                
                <div class="details-section">
                    <h3>Informations personnelles</h3>
                    <div class="details-grid">
                        <div class="detail-item">
                            <div class="detail-label">No d'ordre</div>
                            <div class="detail-value"><?php echo htmlspecialchars($student['no_ordre']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">No de fiche</div>
                            <div class="detail-value"><?php echo htmlspecialchars($student['no_fiche']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Section</div>
                            <div class="detail-value"><?php echo htmlspecialchars($student['section']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Année académique</div>
                            <div class="detail-value"><?php echo htmlspecialchars($student['année']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="details-section">
                    <h3>Notes - Session 1</h3>
                    <div class="grades-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Matière</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td data-label="Matière">Droit Civil</td>
                                    <td data-label="Note"><?php echo htmlspecialchars($student['droit_civil_s1']); ?></td>
                                </tr>
                                <tr>
                                    <td data-label="Matière">Procédure Pénale</td>
                                    <td data-label="Note"><?php echo htmlspecialchars($student['procedure_penale_s1']); ?></td>
                                </tr>
                                <tr>
                                    <td data-label="Matière">Contentieux Administratif</td>
                                    <td data-label="Note"><?php echo htmlspecialchars($student['contentieux_administratif_s1']); ?></td>
                                </tr>
                                <tr>
                                    <td data-label="Matière">Sécurités Sociales</td>
                                    <td data-label="Note"><?php echo htmlspecialchars($student['securites_sociales_s1']); ?></td>
                                </tr>
                                <tr>
                                    <td data-label="Matière">Voies d'Exécution</td>
                                    <td data-label="Note"><?php echo htmlspecialchars($student['voies_execution_s1']); ?></td>
                                </tr>
                                <tr>
                                    <td data-label="Matière">Procédure Commerciale</td>
                                    <td data-label="Note"><?php echo htmlspecialchars($student['procedure_commerciale_s1']); ?></td>
                                </tr>
                                <tr>
                                    <td data-label="Matière">Droits Humains</td>
                                    <td data-label="Note"><?php echo htmlspecialchars($student['droits_humains_s1']); ?></td>
                                </tr>
                                <tr class="total-row">
                                    <td data-label="Matière">Total Session 1</td>
                                    <td data-label="Note"><?php echo htmlspecialchars($student['total_s1']); ?></td>
                                </tr>
                                <tr class="average-row">
                                    <td data-label="Matière">Moyenne Session 1</td>
                                    <td data-label="Note"><?php echo htmlspecialchars($student['moyenne_s1']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="details-section">
                    <h3>Notes - Session 2</h3>
                    <div class="grades-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Matière</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td data-label="Matière">Droit Civil</td>
                                    <td data-label="Note"><?php echo htmlspecialchars($student['droit_civil_s2']); ?></td>
                                </tr>
                                <tr>
                                    <td data-label="Matière">Procédure Pénale</td>
                                    <td data-label="Note"><?php echo htmlspecialchars($student['procedure_penale_s2']); ?></td>
                                </tr>
                                <tr>
                                    <td data-label="Matière">Contentieux Administratif</td>
                                    <td data-label="Note"><?php echo htmlspecialchars($student['contentieux_administratif_s2']); ?></td>
                                </tr>
                                <tr>
                                    <td data-label="Matière">Sécurités Sociales</td>
                                    <td data-label="Note"><?php echo htmlspecialchars($student['securites_sociales_s2']); ?></td>
                                </tr>
                                <tr>
                                    <td data-label="Matière">Voies d'Exécution</td>
                                    <td data-label="Note"><?php echo htmlspecialchars($student['voies_execution_s2']); ?></td>
                                </tr>
                                <tr>
                                    <td data-label="Matière">Procédure Commerciale</td>
                                    <td data-label="Note"><?php echo htmlspecialchars($student['procedure_commerciale_s2']); ?></td>
                                </tr>
                                <tr>
                                    <td data-label="Matière">Droits Humains</td>
                                    <td data-label="Note"><?php echo htmlspecialchars($student['droits_humains_s2']); ?></td>
                                </tr>
                                <tr class="total-row">
                                    <td data-label="Matière">Total Session 2</td>
                                    <td data-label="Note"><?php echo htmlspecialchars($student['total_s2']); ?></td>
                                </tr>
                                <tr class="average-row">
                                    <td data-label="Matière">Moyenne Session 2</td>
                                    <td data-label="Note"><?php echo htmlspecialchars($student['moyenne_s2']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="details-section">
                    <h3>Moyennes par matière</h3>
                    <div class="grades-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Matière</th>
                                    <th>Moyenne</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td data-label="Matière">Droit Civil</td>
                                    <td data-label="Moyenne"><?php echo htmlspecialchars($student['droit_civil']); ?></td>
                                </tr>
                                <tr>
                                    <td data-label="Matière">Procédure Pénale</td>
                                    <td data-label="Moyenne"><?php echo htmlspecialchars($student['procedure_penale']); ?></td>
                                </tr>
                                <tr>
                                    <td data-label="Matière">Contentieux Administratif</td>
                                    <td data-label="Moyenne"><?php echo htmlspecialchars($student['contentieux_administratif']); ?></td>
                                </tr>
                                <tr>
                                    <td data-label="Matière">Sécurités Sociales</td>
                                    <td data-label="Moyenne"><?php echo htmlspecialchars($student['securites_sociales']); ?></td>
                                </tr>
                                <tr>
                                    <td data-label="Matière">Voies d'Exécution</td>
                                    <td data-label="Moyenne"><?php echo htmlspecialchars($student['voies_execution']); ?></td>
                                </tr>
                                <tr>
                                    <td data-label="Matière">Procédure Commerciale</td>
                                    <td data-label="Moyenne"><?php echo htmlspecialchars($student['procedure_commerciale']); ?></td>
                                </tr>
                                <tr>
                                    <td data-label="Matière">Droits Humains</td>
                                    <td data-label="Moyenne"><?php echo htmlspecialchars($student['droits_humains']); ?></td>
                                </tr>
                                <tr class="total-row">
                                    <td data-label="Matière">Total</td>
                                    <td data-label="Moyenne"><?php echo htmlspecialchars($student['total']); ?></td>
                                </tr>
                                <tr class="average-row">
                                    <td data-label="Matière">Moyenne Finale</td>
                                    <td data-label="Moyenne"><?php echo htmlspecialchars($student['moyenne']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <?php if ($student['verdict'] === 'Reprise'): ?>
                <div class="details-section">
                    <h3>Matières à reprendre</h3>
                    <?php 
                    $reprises = json_decode($student['reprises'], true);
                    if (!empty($reprises)): 
                    ?>
                    <ul class="reprises-list">
                        <?php foreach ($reprises as $matiere): ?>
                            <?php 
                            $matiereName = str_replace('_', ' ', $matiere);
                            $matiereName = ucfirst($matiereName);
                            ?>
                            <li><?php echo htmlspecialchars($matiereName); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p>Aucune matière à reprendre.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($isAdmin || $isUserCompta): ?>
                <div class="details-section">
                    <h3>Historique des Paiements</h3>
                    <?php 
                        $totalPaid = getTotalPaidAmount($id);
                        $remainingAmount = getRemainingAmount($id);
                    ?>
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
                    
                    <?php if (empty($payments)): ?>
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
                                        <th>Créé par</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td data-label="Date"><?php echo htmlspecialchars($payment['date_paiement']); ?></td>
                                        <td data-label="Montant"><?php echo htmlspecialchars(number_format($payment['montant'], 2)); ?> HTG</td>
                                        <td data-label="Référence"><?php echo htmlspecialchars($payment['reference']); ?></td>
                                        <td data-label="Statut" class="payment-status <?php echo $payment['status']; ?>">
                                            <?php 
                                                if ($payment['status'] === 'en_attente') echo 'En attente';
                                                elseif ($payment['status'] === 'approuve') echo 'Approuvé';
                                                else echo 'Rejeté';
                                            ?>
                                        </td>
                                        <td data-label="Créé par"><?php echo htmlspecialchars($payment['created_by_username']); ?></td>
                                        <td data-label="Actions" class="actions">
                                            <a href="view_payment.php?id=<?php echo $payment['id']; ?>" class="btn-small">Détails</a>
                                            <?php if ($isAdmin && $payment['status'] === 'en_attente'): ?>
                                            <a href="validate_payment.php?id=<?php echo $payment['id']; ?>" class="btn-small btn-primary">Valider</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($isUserCompta): ?>
                    <div class="form-actions">
                        <a href="add_payment.php?student_id=<?php echo $id; ?>" class="btn btn-payment">Ajouter un Paiement</a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="details-section">
                    <h3>Informations supplémentaires</h3>
                    <div class="details-grid">
                        <div class="detail-item">
                            <div class="detail-label">Date d'ajout</div>
                            <div class="detail-value"><?php echo htmlspecialchars($student['created_at']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Système de Gestion des Étudiants. Tous droits réservés.</p>
        </footer>
    </div>
</body>
</html>