<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $user = authenticateUser($username, $password);
        
        if ($user) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            header("Location: index.php");
            exit();
        } else {
            $error = "Nom d'utilisateur ou mot de passe incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Base de Donné et de Gestion des Étudiants</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.8)), 
                        url('https://images.unsplash.com/photo-1606761568499-6d2451b23c66?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1920&q=80') no-repeat center center;
            background-size: cover;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 450px;
            gap: 50px;
            align-items: center;
        }

        .welcome-section {
            color: white;
            padding: 40px;
        }

        .welcome-section h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .welcome-section p {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .features-list {
            list-style: none;
            padding: 0;
            margin: 30px 0;
        }

        .features-list li {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .features-list li::before {
            content: "✓";
            display: inline-block;
            margin-right: 10px;
            color: #4CAF50;
            font-weight: bold;
        }

        .login-form-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-logo {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 50%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .login-title {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 10px;
        }

        .login-subtitle {
            color: #666;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #4a6fdc;
            box-shadow: 0 0 0 3px rgba(74, 111, 220, 0.1);
            outline: none;
        }

        .login-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4a6fdc, #3a5fc8);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 111, 220, 0.3);
        }

        .error-message {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            border-left: 4px solid #dc3545;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            text-align: center;
            padding: 15px;
            font-size: 0.9rem;
        }

        @media (max-width: 1200px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 600px;
            }

            .welcome-section {
                text-align: center;
                padding: 20px;
            }

            .features-list {
                display: inline-block;
                text-align: left;
            }
        }

        @media (max-width: 480px) {
            .login-form-container {
                padding: 20px;
            }

            .login-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="welcome-section">
            <h1>École de Droit et des Sciences Économiques des Gonaïves</h1>
            <p>Bienvenue sur la plateforme de gestion des étudiants de l'EDSEG. Notre système offre une solution complète pour la gestion académique.</p>
            
            <ul class="features-list">
                <li>Gestion complète des dossiers étudiants</li>
                <li>Suivi des paiements et des frais de scolarité</li>
                <li>Gestion des notes et des résultats académiques</li>
                <li>Génération de rapports et de documents officiels</li>
                <li>Interface intuitive et sécurisée</li>
            </ul>
        </div>

        <div class="login-form-container">
            <div class="login-header">
                <img src="images/logo.jpeg" alt="Logo EDSEG" class="login-logo">
                <h2 class="login-title">Connexion</h2>
                <p class="login-subtitle">Accédez à votre espace de gestion</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <strong>Erreur :</strong> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="login.php">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Entrez votre nom d'utilisateur">
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Entrez votre mot de passe">
                </div>

                <button type="submit" class="login-button">Se connecter</button>
            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Base de Donné et de Gestion des Étudiants de l'École de Droit et des Sciences Économiques des Gonaïves (BDG/EDSEG). Tous droits réservés.</p>
    </footer>
</body>
</html>
