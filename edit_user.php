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
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$id = $_GET['id'];
$user = getUserById($id);

// If user not found, redirect to manage users
if (!$user) {
    header("Location: manage_users.php");
    exit();
}

$success = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? $user['role'];
    
    if (empty($username)) {
        $error = "Le nom d'utilisateur est obligatoire.";
    } else {
        // Update user with or without password change
        if (!empty($password)) {
            $result = updateUserWithPassword($id, $username, $password, $role);
        } else {
            $result = updateUserWithoutPassword($id, $username, $role);
        }
        
        if ($result) {
            $success = "Utilisateur mis à jour avec succès.";
            // Refresh user data
            $user = getUserById($id);
        } else {
            $error = "Erreur lors de la mise à jour de l'utilisateur.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Utilisateur - Système de Gestion</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Modifier un Utilisateur</h1>
            <div class="navigation">
                <a href="manage_users.php" class="btn">Retour à la gestion des utilisateurs</a>
                <a href="index.php" class="btn">Retour à l'accueil</a>
            </div>
        </header>

        <main>
            <div class="form-container">
                <?php if (!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="edit_user.php?id=<?php echo $id; ?>" class="form-section">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Nom d'utilisateur *</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Mot de passe (laisser vide pour ne pas changer)</label>
                            <input type="password" id="password" name="password">
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Rôle *</label>
                            <select id="role" name="role" required>
                                <option value="user_admin" <?php echo $user['role'] === 'user_admin' ? 'selected' : ''; ?>>Utilisateur Administratif</option>
                                <option value="user_compta" <?php echo $user['role'] === 'user_compta' ? 'selected' : ''; ?>>Utilisateur Comptabilité</option>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Système de Gestion. Tous droits réservés.</p>
        </footer>
    </div>
</body>
</html>