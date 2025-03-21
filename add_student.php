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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'no_ordre' => $_POST['no_ordre'] ?? '',
        'nom' => $_POST['nom'] ?? '',
        'prenom' => $_POST['prenom'] ?? '',
        'no_fiche' => $_POST['no_fiche'] ?? '',
        'section' => $_POST['section'] ?? '',
        'année' => intval($_POST['année'] ?? 0),
        // Session 1
        'droit_civil_s1' => intval($_POST['droit_civil_s1'] ?? 0),
        'procedure_penale_s1' => intval($_POST['procedure_penale_s1'] ?? 0),
        'contentieux_administratif_s1' => intval($_POST['contentieux_administratif_s1'] ?? 0),
        'securites_sociales_s1' => intval($_POST['securites_sociales_s1'] ?? 0),
        'voies_execution_s1' => intval($_POST['voies_execution_s1'] ?? 0),
        'procedure_commerciale_s1' => intval($_POST['procedure_commerciale_s1'] ?? 0),
        'droits_humains_s1' => intval($_POST['droits_humains_s1'] ?? 0),
        // Session 2
        'droit_civil_s2' => intval($_POST['droit_civil_s2'] ?? 0),
        'procedure_penale_s2' => intval($_POST['procedure_penale_s2'] ?? 0),
        'contentieux_administratif_s2' => intval($_POST['contentieux_administratif_s2'] ?? 0),
        'securites_sociales_s2' => intval($_POST['securites_sociales_s2'] ?? 0),
        'voies_execution_s2' => intval($_POST['voies_execution_s2'] ?? 0),
        'procedure_commerciale_s2' => intval($_POST['procedure_commerciale_s2'] ?? 0),
        'droits_humains_s2' => intval($_POST['droits_humains_s2'] ?? 0)
    ];
    
    // Validate required fields
    if (empty($data['no_ordre']) || empty($data['nom']) || empty($data['prenom']) || empty($data['no_fiche']) || 
        empty($data['section']) || $data['année'] <= 0) {
        $error = "Tous les champs marqués d'un astérisque (*) sont obligatoires.";
    } else {
        // Check if student with same no_ordre already exists
        $existingStudent = getStudentByNoOrdre($data['no_ordre']);
        if ($existingStudent) {
            $error = "Un étudiant avec ce numéro d'ordre existe déjà.";
        } else {
            $studentId = createStudent($data);
            
            if ($studentId) {
                $success = "Étudiant ajouté avec succès.";
                // Reset form data
                $data = [
                    'no_ordre' => '',
                    'nom' => '',
                    'prenom' => '',
                    'no_fiche' => '',
                    'section' => '',
                    'année' => date('Y'),
                    // Session 1
                    'droit_civil_s1' => 0,
                    'procedure_penale_s1' => 0,
                    'contentieux_administratif_s1' => 0,
                    'securites_sociales_s1' => 0,
                    'voies_execution_s1' => 0,
                    'procedure_commerciale_s1' => 0,
                    'droits_humains_s1' => 0,
                    // Session 2
                    'droit_civil_s2' => 0,
                    'procedure_penale_s2' => 0,
                    'contentieux_administratif_s2' => 0,
                    'securites_sociales_s2' => 0,
                    'voies_execution_s2' => 0,
                    'procedure_commerciale_s2' => 0,
                    'droits_humains_s2' => 0
                ];
            } else {
                $error = "Erreur lors de l'ajout de l'étudiant.";
            }
        }
    }
} else {
    // Default values for the form
    $data = [
        'no_ordre' => '',
        'nom' => '',
        'prenom' => '',
        'no_fiche' => '',
        'section' => '',
        'année' => date('Y'),
        // Session 1
        'droit_civil_s1' => 0,
        'procedure_penale_s1' => 0,
        'contentieux_administratif_s1' => 0,
        'securites_sociales_s1' => 0,
        'voies_execution_s1' => 0,
        'procedure_commerciale_s1' => 0,
        'droits_humains_s1' => 0,
        // Session 2
        'droit_civil_s2' => 0,
        'procedure_penale_s2' => 0,
        'contentieux_administratif_s2' => 0,
        'securites_sociales_s2' => 0,
        'voies_execution_s2' => 0,
        'procedure_commerciale_s2' => 0,
        'droits_humains_s2' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Étudiant - Système de Gestion</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Ajouter un Étudiant</h1>
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
                
                <form method="post" action="add_student.php">
                    <div class="form-section">
                        <h3>Informations personnelles</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="no_ordre">No d'ordre *</label>
                                <input type="text" id="no_ordre" name="no_ordre" value="<?php echo htmlspecialchars($data['no_ordre']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="no_fiche">No de fiche *</label>
                                <input type="text" id="no_fiche" name="no_fiche" value="<?php echo htmlspecialchars($data['no_fiche']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nom">Nom *</label>
                                <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($data['nom']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="prenom">Prénom *</label>
                                <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($data['prenom']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="section">Section *</label>
                                <input type="text" id="section" name="section" value="<?php echo htmlspecialchars($data['section']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="année">Année académique *</label>
                                <input type="number" id="année" name="année" value="<?php echo htmlspecialchars($data['année']); ?>" min="2000" max="2100" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Notes - Session 1</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="droit_civil_s1">Droit Civil (Session 1)</label>
                                <input type="number" id="droit_civil_s1" name="droit_civil_s1" value="<?php echo htmlspecialchars($data['droit_civil_s1']); ?>" min="0" max="100">
                            </div>
                            
                            <div class="form-group">
                                <label for="procedure_penale_s1">Procédure Pénale (Session 1)</label>
                                <input type="number" id="procedure_penale_s1" name="procedure_penale_s1" value="<?php echo htmlspecialchars($data['procedure_penale_s1']); ?>" min="0" max="100">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="contentieux_administratif_s1">Contentieux Administratif (Session 1)</label>
                                <input type="number" id="contentieux_administratif_s1" name="contentieux_administratif_s1" value="<?php echo htmlspecialchars($data['contentieux_administratif_s1']); ?>" min="0" max="100">
                            </div>
                            
                            <div class="form-group">
                                <label for="securites_sociales_s1">Sécurités Sociales (Session 1)</label>
                                <input type="number" id="securites_sociales_s1" name="securites_sociales_s1" value="<?php echo htmlspecialchars($data['securites_sociales_s1']); ?>" min="0" max="100">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="voies_execution_s1">Voies d'Exécution (Session 1)</label>
                                <input type="number" id="voies_execution_s1" name="voies_execution_s1" value="<?php echo htmlspecialchars($data['voies_execution_s1']); ?>" min="0" max="100">
                            </div>
                            
                            <div class="form-group">
                                <label for="procedure_commerciale_s1">Procédure Commerciale (Session 1)</label>
                                <input type="number" id="procedure_commerciale_s1" name="procedure_commerciale_s1" value="<?php echo htmlspecialchars($data['procedure_commerciale_s1']); ?>" min="0" max="100">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="droits_humains_s1">Droits Humains (Session 1)</label>
                                <input type="number" id="droits_humains_s1" name="droits_humains_s1" value="<?php echo htmlspecialchars($data['droits_humains_s1']); ?>" min="0" max="100">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Notes - Session 2</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="droit_civil_s2">Droit Civil (Session 2)</label>
                                <input type="number" id="droit_civil_s2" name="droit_civil_s2" value="<?php echo htmlspecialchars($data['droit_civil_s2']); ?>" min="0" max="100">
                            </div>
                            
                            <div class="form-group">
                                <label for="procedure_penale_s2">Procédure Pénale (Session 2)</label>
                                <input type="number" id="procedure_penale_s2" name="procedure_penale_s2" value="<?php echo htmlspecialchars($data['procedure_penale_s2']); ?>" min="0" max="100">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="contentieux_administratif_s2">Contentieux Administratif (Session 2)</label>
                                <input type="number" id="contentieux_administratif_s2" name="contentieux_administratif_s2" value="<?php echo htmlspecialchars($data['contentieux_administratif_s2']); ?>" min="0" max="100">
                            </div>
                            
                            <div class="form-group">
                                <label for="securites_sociales_s2">Sécurités Sociales (Session 2)</label>
                                <input type="number" id="securites_sociales_s2" name="securites_sociales_s2" value="<?php echo htmlspecialchars($data['securites_sociales_s2']); ?>" min="0" max="100">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="voies_execution_s2">Voies d'Exécution (Session 2)</label>
                                <input type="number" id="voies_execution_s2" name="voies_execution_s2" value="<?php echo htmlspecialchars($data['voies_execution_s2']); ?>" min="0" max="100">
                            </div>
                            
                            <div class="form-group">
                                <label for="procedure_commerciale_s2">Procédure Commerciale (Session 2)</label>
                                <input type="number" id="procedure_commerciale_s2" name="procedure_commerciale_s2" value="<?php echo htmlspecialchars($data['procedure_commerciale_s2']); ?>" min="0" max="100">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="droits_humains_s2">Droits Humains (Session 2)</label>
                                <input type="number" id="droits_humains_s2" name="droits_humains_s2" value="<?php echo htmlspecialchars($data['droits_humains_s2']); ?>" min="0" max="100">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Ajouter l'étudiant</button>
                    </div>
                </form>
            </div>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> Système de Gestion des Étudiants. Tous droits réservés.</p>
        </footer>
    </div>
</body>
</html>