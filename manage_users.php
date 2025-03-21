<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Get all users
$users = getAllUsers();

$success = '';
$error = '';

// Process form submission for adding a new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user_admin';
    
    if (empty($username) || empty($password)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        $userId = createUser($username, $password, $role);
        
        if ($userId) {
            $success = "Utilisateur créé avec succès.";
            // Refresh users list
            $users = getAllUsers();
        } else {
            $error = "Erreur lors de la création de l'utilisateur.";
        }
    }
}

// Process form submission for deleting a user
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $userId = $_GET['id'];
    
    // Don't allow deleting yourself
    if ($userId == $_SESSION['user_id']) {
        $error = "Vous ne pouvez pas supprimer votre propre compte.";
    } else {
        $result = deleteUser($userId);
        
        if ($result) {
            $success = "Utilisateur supprimé avec succès.";
            // Refresh users list
            $users = getAllUsers();
        } else {
            $error = "Erreur lors de la suppression de l'utilisateur.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Système de Gestion</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Gestion des Utilisateurs</h1>
            <div class="navigation">
                <a href="index.php" class="btn">Retour à l'accueil</a>
                <a href="user_permissions.php" class="btn">Gérer les Permissions</a>
            </div>
        </header>

        <main>
            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <h2>Ajouter un Nouvel Utilisateur</h2>
                <form method="post" action="manage_users.php" class="form-section">
                    <input type="hidden" name="action" value="add_user">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Nom d'utilisateur *</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Mot de passe *</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Rôle *</label>
                            <select id="role" name="role" required>
                                <option value="user_admin">Utilisateur Administratif</option>
                                <option value="user_compta">Utilisateur Comptabilité</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
                    </div>
                </form>
            </div>
            
            <div class="user-list">
                <h2>Liste des Utilisateurs</h2>
                
                <?php if (empty($users)): ?>
                    <p>Aucun utilisateur trouvé.</p>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <div class="user-card">
                            <div class="user-info-card">
                                <h3>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                    <span class="user-role <?php echo $user['role']; ?>">
                                        <?php 
                                            if ($user['role'] === 'admin') echo 'Administrateur';
                                            elseif ($user['role'] === 'user_admin') echo 'Utilisateur Administratif';
                                            elseif ($user['role'] === 'user_compta') echo 'Utilisateur Comptabilité';
                                        ?>
                                    </span>
                                </h3>
                                <p>Créé le: <?php echo htmlspecialchars($user['created_at']); ?></p>
                            </div>
                            
                            <div class="user-actions-card">
                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-small">Modifier</a>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="manage_users.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur?');">Supprimer</a>
                                <?php endif; ?>
                                <a href="user_permissions.php?id=<?php echo $user['id']; ?>" class="btn btn-small">Permissions</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Système de Gestion. Tous droits réservés.</p>
        </footer>
    </div>
</body>
</html>