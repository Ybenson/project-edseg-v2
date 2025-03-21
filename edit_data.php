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
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];
$data = getDataById($id);

// If data not found, redirect to index
if (!$data) {
    header("Location: index.php");
    exit();
}

$success = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($name)) {
        $error = "Le nom est obligatoire.";
    } else {
        $result = updateData($id, $name, $description);
        
        if ($result) {
            $success = "Données mises à jour avec succès.";
            // Refresh data
            $data = getDataById($id);
        } else {
            $error = "Erreur lors de la mise à jour des données.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier des Données - Système de Gestion</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Modifier des Données</h1>
            <div class="navigation">
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
                
                <form method="post" action="edit_data.php?id=<?php echo $id; ?>">
                    <div class="form-group">
                        <label for="name">Nom</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($data['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($data['description']); ?></textarea>
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