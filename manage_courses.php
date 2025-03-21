<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$success = '';
$error = '';

// Get all courses
$courses = getAllCourses();

// Process form submission for adding a new course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_course') {
    $code = $_POST['code'] ?? '';
    $name = $_POST['name'] ?? '';
    $coefficient = $_POST['coefficient'] ?? 1;
    $description = $_POST['description'] ?? '';
    
    if (empty($code) || empty($name)) {
        $error = "Le code et le nom de la matière sont obligatoires.";
    } else {
        $courseId = createCourse($code, $name, $coefficient, $description);
        
        if ($courseId) {
            $success = "Matière ajoutée avec succès.";
            // Refresh courses list
            $courses = getAllCourses();
        } else {
            $error = "Erreur lors de l'aj out de la matière.";
        }
    }
}

// Process form submission for deleting a course
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $courseId = $_GET['id'];
    $result = deleteCourse($courseId);
    
    if ($result) {
        $success = "Matière supprimée avec succès.";
        // Refresh courses list
        $courses = getAllCourses();
    } else {
        $error = "Erreur lors de la suppression de la matière.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Matières - Système de Gestion</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Gestion des Matières</h1>
            <div class="navigation">
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
            
            <div class="form-container">
                <h2>Ajouter une Nouvelle Matière</h2>
                <form method="post" action="manage_courses.php" class="form-section">
                    <input type="hidden" name="action" value="add_course">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="code">Code de la matière *</label>
                            <input type="text" id="code" name="code" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="name">Nom de la matière *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="coefficient">Coefficient</label>
                            <input type="number" id="coefficient" name="coefficient" value="1" min="1" max="10">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Ajouter la matière</button>
                    </div>
                </form>
            </div>
            
            <div class="course-list">
                <h2>Liste des Matières</h2>
                
                <?php if (empty($courses)): ?>
                    <p>Aucune matière trouvée.</p>
                <?php else: ?>
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <div class="course-header">
                                <div class="course-title"><?php echo htmlspecialchars($course['name']); ?> (<?php echo htmlspecialchars($course['code']); ?>)</div>
                                <div class="course-actions">
                                    <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="btn btn-small">Modifier</a>
                                    <a href="manage_courses.php?action=delete&id=<?php echo $course['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette matière?');">Supprimer</a>
                                </div>
                            </div>
                            <div class="course-details">
                                <div class="course-detail"><span>Coefficient:</span> <?php echo htmlspecialchars($course['coefficient']); ?></div>
                                <?php if (!empty($course['description'])): ?>
                                    <div class="course-detail"><span>Description:</span> <?php echo htmlspecialchars($course['description']); ?></div>
                                <?php endif; ?>
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

<boltArtifact id="edit-course-php" title="Create edit_course.php file">