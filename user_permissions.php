<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Check if ID is provided
$userId = isset($_GET['id']) ? $_GET['id'] : null;
$user = null;

if ($userId) {
    $user = getUserById($userId);
    
    // If user not found, redirect to manage users
    if (!$user) {
        header("Location: manage_users.php");
        exit();
    }
}

$success = '';
$error = '';

// Get all available permissions
$allPermissions = [
    'students' => [
        'view' => 'Voir les étudiants',
        'add' => 'Ajouter des étudiants',
        'edit' => 'Modifier des étudiants',
        'delete' => 'Supprimer des étudiants',
        'export' => 'Exporter les données des étudiants'
    ],
    'payments' => [
        'view' => 'Voir les paiements',
        'add' => 'Ajouter des paiements',
        'validate' => 'Valider des paiements',
        'report' => 'Générer des rapports de paiement'
    ],
    'courses' => [
        'view' => 'Voir les matières',
        'add' => 'Ajouter des matières',
        'edit' => 'Modifier des matières',
        'delete' => 'Supprimer des matières'
    ],
    'reprise' => [
        'view' => 'Voir les reprises',
        'add' => 'Ajouter des reprises',
        'edit' => 'Modifier des reprises'
    ],
    'reports' => [
        'view' => 'Voir les rapports',
        'generate' => 'Générer des rapports'
    ]
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userId) {
    $permissions = [];
    
    // Collect all selected permissions
    foreach ($allPermissions as $category => $categoryPermissions) {
        foreach ($categoryPermissions as $permission => $label) {
            $key = $category . '_' . $permission;
            if (isset($_POST['permissions'][$key])) {
                $permissions[] = $key;
            }
        }
    }
    
    // Update user permissions
    $result = updateUserPermissions($userId, $permissions);
    
    if ($result) {
        $success = "Permissions mises à jour avec succès.";
    } else {
        $error = "Erreur lors de la mise à jour des permissions.";
    }
}

// Get user's current permissions if user is selected
$userPermissions = [];
if ($userId) {
    $userPermissions = getUserPermissions($userId);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Permissions - Système de Gestion</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Gestion des Permissions</h1>
            <div class="navigation">
                <a href="manage_users.php" class="btn">Retour à la gestion des utilisateurs</a>
                <a href="index.php" class="btn">Retour à l'accueil</a>
            </div>
        </header>

        <main>
            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!$userId): ?>
                <div class="user-list">
                    <h2>Sélectionnez un utilisateur</h2>
                    <?php 
                    $users = getAllUsers();
                    if (empty($users)): 
                    ?>
                        <p>Aucun utilisateur trouvé.</p>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <div class="user-card">
                                <div class="user-info-card">
                                    <h3>
                                        <?php echo htmlspecialchars($u['username']); ?>
                                        <span class="user-role <?php echo $u['role']; ?>">
                                            <?php 
                                                if ($u['role'] === 'admin') echo 'Administrateur';
                                                elseif ($u['role'] === 'user_admin') echo 'Utilisateur Administratif';
                                                elseif ($u['role'] === 'user_compta') echo 'Utilisateur Comptabilité';
                                            ?>
                                        </span>
                                    </h3>
                                </div>
                                
                                <div class="user-actions-card">
                                    <a href="user_permissions.php?id=<?php echo $u['id']; ?>" class="btn btn-small">Gérer les permissions</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="form-container">
                    <h2>Permissions pour <?php echo htmlspecialchars($user['username']); ?></h2>
                    <p>Rôle: 
                        <span class="user-role <?php echo $user['role']; ?>">
                            <?php 
                                if ($user['role'] === 'admin') echo 'Administrateur';
                                elseif ($user['role'] === 'user_admin') echo 'Utilisateur Administratif';
                                elseif ($user['role'] === 'user_compta') echo 'Utilisateur Comptabilité';
                            ?>
                        </span>
                    </p>
                    
                    <form method="post" action="user_permissions.php?id=<?php echo $userId; ?>">
                        <?php if ($user['role'] === 'admin'): ?>
                            <div class="permission-section">
                                <p>Les administrateurs ont automatiquement toutes les permissions.</p>
                            </div>
                        <?php else: ?>
                            <div class="permission-section">
                                <?php foreach ($allPermissions as $category => $categoryPermissions): ?>
                                    <div class="permission-group">
                                        <h4><?php echo ucfirst($category); ?></h4>
                                        <?php foreach ($categoryPermissions as $permission => $label): ?>
                                            <?php 
                                                $key = $category . '_' . $permission;
                                                $checked = in_array($key, $userPermissions) ? 'checked' : '';
                                                
                                                // Disable certain permissions based on role
                                                $disabled = '';
                                                if ($user['role'] === 'user_compta' && $category !== 'payments') {
                                                    $disabled = 'disabled';
                                                }
                                                if ($user['role'] === 'user_admin' && $category === 'payments') {
                                                    $disabled = 'disabled';
                                                }
                                            ?>
                                            <div class="permission-item">
                                                <label class="checkbox-label">
                                                    <input type="checkbox" name="permissions[<?php echo $key; ?>]" <?php echo $checked; ?> <?php echo $disabled; ?>>
                                                    <?php echo $label; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Enregistrer les permissions</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Système de Gestion. Tous droits réservés.</p>
        </footer>
    </div>
</body>
</html>