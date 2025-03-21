<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information
$user = getUserById($_SESSION['user_id']);
$isAdmin = $user['role'] === 'admin';
$isUserAdmin = $user['role'] === 'user_admin';
$isUserCompta = $user['role'] === 'user_compta';

// Get search parameters
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$year = isset($_GET['year']) ? $_GET['year'] : '';
$section = isset($_GET['section']) ? $_GET['section'] : '';

// Get all available years for the filter
$years = getYears();

// Get all available sections for the filter
$sections = getSections();

// Get students based on search criteria, but only if search was performed
$searchPerformed = !empty($searchTerm) || !empty($year) || !empty($section);
$students = $searchPerformed ? searchStudents($searchTerm, $year, $section) : [];

// Get unread notifications count
$unreadNotifications = getUnreadNotificationsCount($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Base de Donné et de Gestion des Étudiants de l'EDSEG</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .admin-panel {
            background: linear-gradient(to right, #f8f9fa, #ffffff);
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 30px;
        }

        .admin-panel h2 {
            color: #2c3e50;
            font-size: 1.5rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #4a6fdc;
        }

        .admin-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .admin-btn {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 15px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            text-decoration: none;
            color: white;
            background: linear-gradient(135deg, #4a6fdc, #3a5fc8);
            border: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .admin-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .admin-btn.users {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }

        .admin-btn.students {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .admin-btn.courses {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }

        .admin-btn.documents {
            background: linear-gradient(135deg, #e67e22, #d35400);
        }

        .admin-btn.payments {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .admin-btn.pending {
            background: linear-gradient(135deg, #f1c40f, #f39c12);
        }

        .admin-btn i {
            margin-right: 10px;
            font-size: 1.2em;
        }

        .admin-btn-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin-left: auto;
        }

        @media (max-width: 768px) {
            .admin-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Base de Donné et de Gestion des Étudiants de l'École de Droit et des Sciences Économiques des Gonaïves (BDG/EDSEG)</h1>
            <div class="user-info">
                <p>Connecté en tant que: <strong><?php echo htmlspecialchars($user['username']); ?></strong> 
                   (<?php 
                        if ($isAdmin) echo 'Administrateur';
                        elseif ($isUserAdmin) echo 'Utilisateur Administratif';
                        elseif ($isUserCompta) echo 'Utilisateur Comptabilité';
                    ?>)
                </p>
                <div class="user-actions">
                    <a href="notifications.php" class="btn btn-notification">
                        Notifications
                        <?php if ($unreadNotifications > 0): ?>
                        <span class="notification-badge"><?php echo $unreadNotifications; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="logout.php" class="btn btn-logout">Déconnexion</a>
                </div>
            </div>
        </header>

        <main>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message"><?php echo $_SESSION['error_message']; ?></div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <?php if ($isAdmin): ?>
            <div class="admin-panel">
                <h2>Panneau d'Administration</h2>
                <div class="admin-actions">
                    <a href="manage_users.php" class="admin-btn users">
                        <i>👥</i>
                        Gérer les Utilisateurs
                    </a>
                    <a href="add_student.php" class="admin-btn students">
                        <i>👨‍🎓</i>
                        Ajouter un Étudiant
                    </a>
                    <a href="manage_courses.php" class="admin-btn courses">
                        <i>📚</i>
                        Gérer les Matières
                    </a>
                    <a href="document_logs.php" class="admin-btn documents">
                        <i>📄</i>
                        Consulter les Accès aux Documents
                    </a>
                    <a href="pending_payments.php" class="admin-btn pending">
                        <i>⏳</i>
                        Paiements en Attente
                        <span class="admin-btn-count">3</span>
                    </a>
                    <a href="all_payments.php" class="admin-btn payments">
                        <i>💰</i>
                        Tous les Paiements
                    </a>
                </div>
            </div>
            <?php elseif ($isUserCompta): ?>
            <div class="admin-panel">
                <h2>Panneau de Comptabilité</h2>
                <div class="admin-actions">
                    <a href="all_payments.php" class="admin-btn payments">
                        <i>💰</i>
                        Tous les Paiements
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <div class="search-section">
                <h2>Recherche d'Étudiants</h2>
                <form method="get" action="index.php" class="search-form">
                    <div class="search-row">
                        <div class="search-group">
                            <label for="search">Rechercher</label>
                            <input type="text" id="search" name="search" placeholder="No d'ordre, Nom, Prénom ou No de fiche" value="<?php echo htmlspecialchars($searchTerm); ?>">
                        </div>
                        
                        <div class="search-group">
                            <label for="year">Année</label>
                            <select id="year" name="year">
                                <option value="">Toutes les années</option>
                                <?php foreach ($years as $y): ?>
                                <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="search-group">
                            <label for="section">Section</label>
                            <select id="section" name="section">
                                <option value="">Toutes les sections</option>
                                <?php foreach ($sections as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $section == $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="search-group search-button-group">
                            <button type="submit" class="btn btn-primary">Rechercher</button>
                            <a href="index.php" class="btn">Réinitialiser</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="data-section">
                <h2>Liste des Étudiants</h2>
                
                <?php if (!$searchPerformed): ?>
                    <div class="search-prompt">
                        <p>Veuillez effectuer une recherche pour afficher les étudiants.</p>
                    </div>
                <?php elseif (empty($students)): ?>
                    <p>Aucun étudiant trouvé pour votre recherche.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>No d'ordre</th>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Section</th>
                                    <th>Année</th>
                                    <?php if (!$isUserCompta): ?>
                                    <th>Moyenne</th>
                                    <th>Verdict</th>
                                    <?php endif; ?>
                                    <th>Paiement</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <?php 
                                    $hasPaiement = hasApprovedPayment($student['id']); 
                                    $totalPaid = getTotalPaidAmount($student['id']);
                                    $remainingAmount = getRemainingAmount($student['id']);
                                ?>
                                <tr>
                                    <td data-label="No d'ordre"><?php echo htmlspecialchars($student['no_ordre']); ?></td>
                                    <td data-label="Nom"><?php echo htmlspecialchars($student['nom']); ?></td>
                                    <td data-label="Prénom"><?php echo htmlspecialchars($student['prenom']); ?></td>
                                    <td data-label="Section"><?php echo htmlspecialchars($student['section']); ?></td>
                                    <td data-label="Année"><?php echo htmlspecialchars($student['année']); ?></td>
                                    <?php if (!$isUserCompta): ?>
                                    <td data-label="Moyenne">
                                        <?php if ($isUserAdmin && !$hasPaiement): ?>
                                            <span class="masked-data">***</span>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($student['moyenne']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Verdict">
                                        <?php if ($isUserAdmin && !$hasPaiement): ?>
                                            <span class="masked-data">***</span>
                                        <?php else: ?>
                                            <span class="verdict <?php echo strtolower($student['verdict']); ?>"><?php echo htmlspecialchars($student['verdict']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                    <td data-label="Paiement">
                                        <span class="payment-status <?php echo $hasPaiement ? 'paid' : 'unpaid'; ?>">
                                            <?php if ($hasPaiement): ?>
                                                Payé
                                            <?php else: ?>
                                                Non payé
                                            <?php endif; ?>
                                        </span>
                                     </td>
                                    <td data-label="Actions" class="actions">
                                        <?php if ($isUserAdmin && !$hasPaiement): ?>
                                            <span class="btn-small btn-disabled" title="Paiement requis pour voir les détails">Voir</span>
                                        <?php elseif (!$isUserCompta): ?>
                                            <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn-small">Voir</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($isUserCompta): ?>
                                            <a href="add_payment.php?student_id=<?php echo $student['id']; ?>" class="btn-small btn-payment">Paiement</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($isAdmin): ?>
                                            <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn-small">Modifier</a>
                                            <a href="delete_student.php?id=<?php echo $student['id']; ?>" class="btn-small btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet étudiant?');">Supprimer</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <footer>
            <div class="footer-content">
                <img src="images/ueh.jpeg" alt="UEH Logo" class="footer-logo" />
                <p>&copy; <?php echo date('Y'); ?> Base de Donné et de Gestion des Étudiants de l'École de Droit et des Sciences Économiques des Gonaïves (BDG/EDSEG). Tous droits réservés.</p>
            </div>
        </footer>
    </div>
</body>
</html>