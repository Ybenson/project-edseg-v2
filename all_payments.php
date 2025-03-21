<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'user_compta')) {
    header("Location: index.php");
    exit();
}

// Get user information
$user = getUserById($_SESSION['user_id']);
$isAdmin = $user['role'] === 'admin';
$isUserCompta = $user['role'] === 'user_compta';

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// // Filters
$filters = [];

if (isset($_GET['student_id']) && !empty($_GET['student_id'])) {
    $filters['student_id'] = $_GET['student_id'];
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}

if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}

// Get payments
$payments = getAllPayments($limit, $offset, $filters);
$totalPayments = countAllPayments($filters);
$totalPages = ceil($totalPayments / $limit);

// Get all students for filter
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT id, nom, prenom, no_ordre FROM students ORDER BY nom ASC, prenom ASC");
$stmt->execute();
$result = $stmt->get_result();
$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tous les Paiements - Système de Gestion</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Tous les Paiements</h1>
            <div class="navigation">
                <a href="index.php" class="btn">Retour à l'accueil</a>
                <?php if ($isAdmin): ?>
                <a href="pending_payments.php" class="btn">Paiements en attente</a>
                <?php endif; ?>
            </div>
        </header>

        <main>
            <div class="search-section">
                <h2>Filtrer les Paiements</h2>
                <form method="get" action="all_payments.php" class="search-form">
                    <div class="search-row">
                        <div class="search-group">
                            <label for="student_id">Étudiant</label>
                            <select id="student_id" name="student_id">
                                <option value="">Tous les étudiants</option>
                                <?php foreach ($students as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo isset($filters['student_id']) && $filters['student_id'] == $s['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['nom'] . ' ' . $s['prenom'] . ' (' . $s['no_ordre'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="search-group">
                            <label for="status">Statut</label>
                            <select id="status" name="status">
                                <option value="">Tous les statuts</option>
                                <option value="en_attente" <?php echo isset($filters['status']) && $filters['status'] == 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                <option value="approuve" <?php echo isset($filters['status']) && $filters['status'] == 'approuve' ? 'selected' : ''; ?>>Approuvé</option>
                                <option value="rejete" <?php echo isset($filters['status']) && $filters['status'] == 'rejete' ? 'selected' : ''; ?>>Rejeté</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="search-row">
                        <div class="search-group">
                            <label for="date_from">Date de début</label>
                            <input type="date" id="date_from" name="date_from" value="<?php echo isset($filters['date_from']) ? $filters['date_from'] : ''; ?>">
                        </div>
                        
                        <div class="search-group">
                            <label for="date_to">Date de fin</label>
                            <input type="date" id="date_to" name="date_to" value="<?php echo isset($filters['date_to']) ? $filters['date_to'] : ''; ?>">
                        </div>
                        
                        <div class="search-group search-button-group">
                            <button type="submit" class="btn btn-primary">Filtrer</button>
                            <a href="all_payments.php" class="btn">Réinitialiser</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="data-section">
                <h2>Liste des Paiements</h2>
                
                <?php if (empty($payments)): ?>
                    <p>Aucun paiement trouvé.</p>
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
                                    <th>Statut</th>
                                    <th>Créé par</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td data-label="Date"><?php echo htmlspecialchars($payment['date_paiement']); ?></td>
                                    <td data-label="Étudiant"><?php echo htmlspecialchars($payment['student_nom'] . ' ' . $payment['student_prenom']); ?></td>
                                    <td data-label="No d'ordre"><?php echo htmlspecialchars($payment['no_ordre']); ?></td>
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
                                        <?php if ($isUserCompta): ?>
                                        <a href="add_payment.php?student_id=<?php echo $payment['student_id']; ?>" class="btn-small btn-payment">Paiement</a>
                                        <?php else: ?>
                                        <a href="view_student.php?id=<?php echo $payment['student_id']; ?>" class="btn-small">Voir l'étudiant</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="all_payments.php?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($filters)); ?>" class="btn-small">&laquo; Précédent</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current-page"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="all_payments.php?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($filters)); ?>" class="btn-small"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="all_payments.php?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($filters)); ?>" class="btn-small">Suivant &raquo;</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Système de Gestion des Étudiants. Tous droits réservés.</p>
        </footer>
    </div>
</body>
</html>