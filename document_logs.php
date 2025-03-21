<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Get user information
$user = getUserById($_SESSION['user_id']);

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$filters = [];

if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $filters['user_id'] = $_GET['user_id'];
}

if (isset($_GET['student_id']) && !empty($_GET['student_id'])) {
    $filters['student_id'] = $_GET['student_id'];
}

if (isset($_GET['document_type']) && !empty($_GET['document_type'])) {
    $filters['document_type'] = $_GET['document_type'];
}

if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}

// Get logs
$logs = getDocumentAccessLogs($limit, $offset, $filters);
$totalLogs = countDocumentAccessLogs($filters);
$totalPages = ceil($totalLogs / $limit);

// Get all users for filter
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT id, username FROM users ORDER BY username ASC");
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Get all students for filter
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
    <title>Logs d'Accès aux Documents - Système de Gestion</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Logs d'Accès aux Documents</h1>
            <div class="navigation">
                <a href="index.php" class="btn">Retour à l'accueil</a>
            </div>
        </header>

        <main>
            <div class="search-section">
                <h2>Filtrer les Logs</h2>
                <form method="get" action="document_logs.php" class="search-form">
                    <div class="search-row">
                        <div class="search-group">
                            <label for="user_id">Utilisateur</label>
                            <select id="user_id" name="user_id">
                                <option value="">Tous les utilisateurs</option>
                                <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo isset($filters['user_id']) && $filters['user_id'] == $u['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['username']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
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
                            <label for="document_type">Type de document</label>
                            <select id="document_type" name="document_type">
                                <option value="">Tous les types</option>
                                <option value="pdf" <?php echo isset($filters['document_type']) && $filters['document_type'] == 'pdf' ? 'selected' : ''; ?>>PDF</option>
                                <option value="word" <?php echo isset($filters['document_type']) && $filters['document_type'] == 'word' ? 'selected' : ''; ?>>Word</option>
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
                            <a href="document_logs.php" class="btn">Réinitialiser</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="data-section">
                <h2>Liste des Accès aux Documents</h2>
                
                <?php if (empty($logs)): ?>
                    <p>Aucun log d'accès trouvé.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date et Heure</th>
                                    <th>Utilisateur</th>
                                    <th>Étudiant</th>
                                    <th>Type de Document</th>
                                    <th>Adresse IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['access_time']); ?></td>
                                    <td><?php echo htmlspecialchars($log['username']); ?></td>
                                    <td><?php echo htmlspecialchars($log['nom'] . ' ' . $log['prenom'] . ' (' . $log['no_ordre'] . ')'); ?></td>
                                    <td><?php echo htmlspecialchars(strtoupper($log['document_type'])); ?></td>
                                    <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="document_logs.php?page=<?php echo $page - 1; ?><?php echo http_build_query(array_filter($filters)); ?>" class="btn-small">&laquo; Précédent</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current-page"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="document_logs.php?page=<?php echo $i; ?><?php echo http_build_query(array_filter($filters)); ?>" class="btn-small"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="document_logs.php?page=<?php echo $page + 1; ?><?php echo http_build_query(array_filter($filters)); ?>" class="btn-small">Suivant &raquo;</a>
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