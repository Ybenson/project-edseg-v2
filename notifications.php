<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Mark notification as read if requested
if (isset($_GET['mark_read']) && !empty($_GET['mark_read'])) {
    $notificationId = $_GET['mark_read'];
    markNotificationAsRead($notificationId);
    
    // Redirect to the link if provided
    if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
        header("Location: " . $_GET['redirect']);
        exit();
    }
    
    // Redirect back to notifications page
    header("Location: notifications.php");
    exit();
}

// Mark all notifications as read if requested
if (isset($_GET['mark_all_read'])) {
    markAllNotificationsAsRead($_SESSION['user_id']);
    
    // Redirect back to notifications page
    header("Location: notifications.php");
    exit();
}

// Get user notifications
$notifications = getUserNotifications($_SESSION['user_id'], 50);
$unreadCount = getUnreadNotificationsCount($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Système de Gestion</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Notifications</h1>
            <div class="navigation">
                <a href="index.php" class="btn">Retour à l'accueil</a>
                <?php if ($unreadCount > 0): ?>
                <a href="notifications.php?mark_all_read=1" class="btn">Marquer tout comme lu</a>
                <?php endif; ?>
            </div>
        </header>

        <main>
            <div class="notifications-container">
                <h2>Vos Notifications</h2>
                
                <?php if (empty($notifications)): ?>
                    <p>Vous n'avez aucune notification.</p>
                <?php else: ?>
                    <div class="notifications-list">
                        <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                            <div class="notification-content">
                                <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                <div class="notification-time"><?php echo htmlspecialchars($notification['created_at']); ?></div>
                            </div>
                            <div class="notification-actions">
                                <?php if (!empty($notification['link'])): ?>
                                <a href="notifications.php?mark_read=<?php echo $notification['id']; ?>&redirect=<?php echo urlencode($notification['link']); ?>" class="btn-small">Voir</a>
                                <?php endif; ?>
                                
                                <?php if (!$notification['is_read']): ?>
                                <a href="notifications.php?mark_read=<?php echo $notification['id']; ?>" class="btn-small">Marquer comme lu</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
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