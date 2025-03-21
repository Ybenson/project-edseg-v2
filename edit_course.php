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
    header("Location: manage_courses.php");
    exit();
}

$id = $_GET['id'];
$course = getCourseById($id);

// If course not found, redirect to manage courses
if (!$course) {
    header("Location: manage_courses.php");
    exit();
}

$success = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    $name = $_POST['name'] ?? '';
    $coefficient = $_POST['coefficient'] ?? 1;
    $description = $_POST['description'] ?? '';
    
    if (empty($code) || empty($name)) {
        $error = "Le code et le nom de la matière sont obligatoires.";
    } else {
        $result = updateCourse($id, $code, $name, $coefficient, $description);
        
        if ($result) {
            $success = "Matière mise à jour avec succès.";
            // Refresh course data
            $course = getCourseById($id);
        } else {
            $error = "Erreur lors de la mise à jour de la matière.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une Matière - Système de Gestion</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Modifier une Matière</h1>
            <div class="navigation">
                <a href="manage_courses.php" class="btn">Retour à la gestion des matières</a>
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
                
                <form method="post" action="edit_course.php?id=<?php echo $id; ?>" class="form-section">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="code">Code de la matière *</label>
                            <input type="text" id="code" name="code" value="<?php echo htmlspecialchars($course['code']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="name">Nom de la matière *</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($course['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="coefficient">Coefficient</label>
                            <input type="number" id="coefficient" name="coefficient" value="<?php echo htmlspecialchars($course['coefficient']); ?>" min="1" max="10">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($course['description']); ?></textarea>
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