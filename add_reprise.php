<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Vérifier si l'ID de l'étudiant est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];
$student = getStudentById($id);

// Si l'étudiant n'est pas trouvé, rediriger vers l'index
if (!$student || $student['verdict'] !== 'Reprise') {
    header("Location: index.php");
    exit();
}

$success = '';
$error = '';

// Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matiere = $_POST['matiere'] ?? '';
    $note = intval($_POST['note'] ?? 0);
    
    if (empty($matiere) || $note < 0 || $note > 100) {
        $error = "Veuillez fournir une note valide (entre 0 et 100).";
    } else {
        if (addRepriseNote($id, $matiere, $note)) {
            $success = "Note de reprise ajoutée avec succès.";
            // Rafraîchir les données de l'étudiant
            $student = getStudentById($id);
        } else {
            $error = "Erreur lors de l'ajout de la note de reprise.";
        }
    }
}

// Obtenir les reprises existantes
$reprises = getStudentReprises($id);
$reprisesMatiere = json_decode($student['reprises'], true) ?? [];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une Note de Reprise - Système de Gestion</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Ajouter une Note de Reprise</h1>
            <div class="navigation">
                <a href="view_student.php?id=<?php echo $id; ?>" class="btn">Retour aux détails de l'étudiant</a>
                <a href="index.php" class="btn">Retour à l'accueil</a>
            </div>
        </header>

        <main>
            <div class="student-info">
                <h2><?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenom']); ?></h2>
                <p>No d'ordre: <?php echo htmlspecialchars($student['no_ordre']); ?></p>
            </div>

            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="form-container">
                <h3>Matières à reprendre</h3>
                <form method="post" action="add_reprise.php?id=<?php echo $id; ?>" class="form-section">
                    <div class="form-group">
                        <label for="matiere">Matière *</label>
                        <select id="matiere" name="matiere" required>
                            <option value="">Sélectionner une matière</option>
                            <?php foreach ($reprisesMatiere as $matiere): ?>
                                <option value="<?php echo $matiere; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $matiere)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="note">Note de reprise *</label>
                        <input type="number" id="note" name="note" min="0" max="100" required>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Ajouter la note</button>
                    </div>
                </form>

                <?php if (!empty($reprises)): ?>
                    <h3>Notes de reprise existantes</h3>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Matière</th>
                                    <th>Note</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reprises as $reprise): ?>
                                    <tr>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $reprise['matiere'])); ?></td>
                                        <td><?php echo $reprise['note_reprise']; ?></td>
                                        <td><?php echo $reprise['date_reprise']; ?></td>
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
