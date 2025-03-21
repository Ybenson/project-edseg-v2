<?php
// Database setup script
require_once 'config.php';

// Create database if it doesn't exist
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    echo "Base de données créée avec succès ou existe déjà<br>";
} else {
    echo "Erreur lors de la création de la base de données: " . $conn->error . "<br>";
}
$conn->close();

// Connect to the database
$conn = getDbConnection();

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user_admin', 'user_compta') NOT NULL DEFAULT 'user_admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "Table des utilisateurs créée avec succès ou existe déjà<br>";
} else {
    echo "Erreur lors de la création de la table des utilisateurs: " . $conn->error . "<br>";
}

// Create user_permissions table
$sql = "CREATE TABLE IF NOT EXISTS user_permissions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    permission VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY user_permission (user_id, permission),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "Table des permissions utilisateurs créée avec succès ou existe déjà<br>";
} else {
    echo "Erreur lors de la création de la table des permissions utilisateurs: " . $conn->error . "<br>";
}

// Create courses table
$sql = "CREATE TABLE IF NOT EXISTS courses (
    id INT(11) NOT NULL AUTO_INCREMENT,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    coefficient INT NOT NULL DEFAULT 1,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "Table des matières créée avec succès ou existe déjà<br>";
} else {
    echo "Erreur lors de la création de la table des matières: " . $conn->error . "<br>";
}

// Create students table
$sql = "CREATE TABLE IF NOT EXISTS students (
    id INT(11) NOT NULL AUTO_INCREMENT,
    no_ordre VARCHAR(10) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    no_fiche VARCHAR(50) NOT NULL,
    section VARCHAR(50) NOT NULL,
    année INT NOT NULL,
    droit_civil INT NOT NULL DEFAULT 0,
    procedure_penale INT NOT NULL DEFAULT 0,
    contentieux_administratif INT NOT NULL DEFAULT 0,
    securites_sociales INT NOT NULL DEFAULT 0,
    voies_execution INT NOT NULL DEFAULT 0,
    procedure_commerciale INT NOT NULL DEFAULT 0,
    droits_humains INT NOT NULL DEFAULT 0,
    droit_civil_s1 INT NOT NULL DEFAULT 0,
    procedure_penale_s1 INT NOT NULL DEFAULT 0,
    contentieux_administratif_s1 INT NOT NULL DEFAULT 0,
    securites_sociales_s1 INT NOT NULL DEFAULT 0,
    voies_execution_s1 INT NOT NULL DEFAULT 0,
    procedure_commerciale_s1 INT NOT NULL DEFAULT 0,
    droits_humains_s1 INT NOT NULL DEFAULT 0,
    total_s1 INT NOT NULL DEFAULT 0,
    moyenne_s1 DECIMAL(5,2) NOT NULL DEFAULT 0,
    droit_civil_s2 INT NOT NULL DEFAULT 0,
    procedure_penale_s2 INT NOT NULL DEFAULT 0,
    contentieux_administratif_s2 INT NOT NULL DEFAULT 0,
    securites_sociales_s2 INT NOT NULL DEFAULT 0,
    voies_execution_s2 INT NOT NULL DEFAULT 0,
    procedure_commerciale_s2 INT NOT NULL DEFAULT 0,
    droits_humains_s2 INT NOT NULL DEFAULT 0,
    total_s2 INT NOT NULL DEFAULT 0,
    moyenne_s2 DECIMAL(5,2) NOT NULL DEFAULT 0,
    total INT NOT NULL DEFAULT 0,
    moyenne DECIMAL(5,2) NOT NULL DEFAULT 0,
    verdict VARCHAR(50) NOT NULL DEFAULT 'Non évalué',
    reprises TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "Table des étudiants créée avec succès ou existe déjà<br>";
} else {
    echo "Erreur lors de la création de la table des étudiants: " . $conn->error . "<br>";
}

// Create student_sessions table
$sql = "CREATE TABLE IF NOT EXISTS student_sessions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    student_id INT(11) NOT NULL,
    session_year INT NOT NULL,
    session_number INT NOT NULL,
    average DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "Table des sessions d'étudiants créée avec succès ou existe déjà<br>";
} else {
    echo "Erreur lors de la création de la table des sessions d'étudiants: " . $conn->error . "<br>";
}

// Create session_grades table
$sql = "CREATE TABLE IF NOT EXISTS session_grades (
    id INT(11) NOT NULL AUTO_INCREMENT,
    session_id INT(11) NOT NULL,
    course_id INT(11) NOT NULL,
    grade INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (session_id) REFERENCES student_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "Table des notes de session créée avec succès ou existe déjà<br>";
} else {
    echo "Erreur lors de la création de la table des notes de session: " . $conn->error . "<br>";
}

// Create student_reprises table
$sql = "CREATE TABLE IF NOT EXISTS student_reprises (
    id INT(11) NOT NULL AUTO_INCREMENT,
    student_id INT(11) NOT NULL,
    course_id INT(11) NOT NULL,
    original_grade INT NOT NULL,
    reprise_grade INT NOT NULL,
    reprise_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "Table des reprises d'étudiants créée avec succès ou existe déjà<br>";
} else {
    echo "Erreur lors de la création de la table des reprises d'étudiants: " . $conn->error . "<br>";
}

// Create payments table
$sql = "CREATE TABLE IF NOT EXISTS payments (
    id INT(11) NOT NULL AUTO_INCREMENT,
    student_id INT(11) NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    reference VARCHAR(50) NOT NULL,
    date_paiement DATE NOT NULL,
    status ENUM('en_attente', 'approuve', 'rejete') NOT NULL DEFAULT 'en_attente',
    commentaire TEXT NULL,
    created_by INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by INT(11) NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "Table des paiements créée avec succès ou existe déjà<br>";
} else {
    echo "Erreur lors de la création de la table des paiements: " . $conn->error . "<br>";
}

// Create notifications table
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "Table des notifications créée avec succès ou existe déjà<br>";
} else {
    echo "Erreur lors de la création de la table des notifications: " . $conn->error . "<br>";
}

// Create document_access_logs table
$sql = "CREATE TABLE IF NOT EXISTS document_access_logs (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    student_id INT(11) NOT NULL,
    document_type VARCHAR(20) NOT NULL,
    access_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) DEFAULT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "Table des logs d'accès aux documents créée avec succès ou existe déjà<br>";
} else {
    echo "Erreur lors de la création de la table des logs d'accès aux documents: " . $conn->error . "<br>";
}

// Check if admin user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Create default admin user
    $username = 'admin';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $role = 'admin';
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);
    
    if ($stmt->execute()) {
        echo "Utilisateur admin par défaut créé avec succès<br>";
        echo "Nom d'utilisateur: admin<br>";
        echo "Mot de passe: admin123<br>";
        echo "<strong>Veuillez changer ce mot de passe après la première connexion!</strong><br>";
    } else {
        echo "Erreur lors de la création de l'utilisateur admin par défaut: " . $stmt->error . "<br>";
    }
}

// Create default user_admin and user_compta if they don't exist
$stmt = $conn->prepare("SELECT id FROM users WHERE role = 'user_admin' LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $username = 'administratif';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $role = 'user_admin';
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);
    
    if ($stmt->execute()) {
        echo "Utilisateur administratif par défaut créé avec succès<br>";
        echo "Nom d'utilisateur: administratif<br>";
        echo "Mot de passe: admin123<br>";
    } else {
        echo "Erreur lors de la création de l'utilisateur administratif par défaut: " . $stmt->error . "<br>";
    }
}

$stmt = $conn->prepare("SELECT id FROM users WHERE role = 'user_compta' LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $username = 'comptabilite';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $role = 'user_compta';
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);
    
    if ($stmt->execute()) {
        echo "Utilisateur comptabilité par défaut créé avec succès<br>";
        echo "Nom d'utilisateur: comptabilite<br>";
        echo "Mot de passe: admin123<br>";
    } else {
        echo "Erreur lors de la création de l'utilisateur comptabilité par défaut: " . $stmt->error . "<br>";
    }
}

// Add default courses if the courses table is empty
$stmt = $conn->prepare("SELECT id FROM courses LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $defaultCourses = [
        ['DC101', 'Droit Civil', 1, 'Cours de droit civil'],
        ['PP102', 'Procédure Pénale', 1, 'Cours de procédure pénale'],
        ['CA103', 'Contentieux Administratif', 1, 'Cours de contentieux administratif'],
        ['SS104', 'Sécurités Sociales', 1, 'Cours de sécurités sociales'],
        ['VE105', 'Voies d\'Exécution', 1, 'Cours de voies d\'exécution'],
        ['PC106', 'Procédure Commerciale', 1, 'Cours de procédure commerciale'],
        ['DH107', 'Droits Humains', 1, 'Cours de droits humains']
    ];
    
    foreach ($defaultCourses as $course) {
        $sql = "INSERT INTO courses (code, name, coefficient, description) VALUES ('{$course[0]}', '{$course[1]}', {$course[2]}, '{$course[3]}')";
        if ($conn->query($sql)) {
            echo "Matière {$course[1]} ajoutée avec succès<br>";
        } else {
            echo "Erreur lors de l'ajout de la matière {$course[1]}: " . $conn->error . "<br>";
        }
    }
    
    echo "Matières par défaut ajoutées avec succès<br>";
}

// Add some sample student data if the students table is empty
$stmt = $conn->prepare("SELECT id FROM students LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Insert sample data using direct SQL queries instead of prepared statements with many parameters
    $sampleStudents = [
        ['E001', 'Dupont', 'Jean', 'F12345', 'Juridique', 2023, 75, 80, 70, 85, 78, 82, 76, 546, 78.00, 'Réussi'],
        ['E002', 'Martin', 'Sophie', 'F12346', 'Juridique', 2023, 65, 70, 68, 72, 75, 80, 82, 512, 73.14, 'Réussi'],
        ['E003', 'Dubois', 'Pierre', 'F12347', 'Juridique', 2022, 55, 60, 58, 62, 65, 59, 61, 420, 60.00, 'Réintégration'],
        ['E004', 'Lefebvre', 'Marie', 'F12348', 'Juridique', 2022, 45, 48, 42, 40, 38, 44, 43, 300, 42.86, 'Échec'],
        ['E005', 'Bernard', 'Thomas', 'F12349', 'Juridique', 2023, 85, 88, 90, 92, 87, 86, 89, 617, 88.14, 'Réussi'],
        ['E006', 'Petit', 'Emma', 'F12350', 'Sciences Politiques', 2023, 72, 75, 78, 80, 76, 74, 77, 532, 76.00, 'Réussi'],
        ['E007', 'Robert', 'Lucas', 'F12351', 'Sciences Politiques', 2022, 62, 65, 60, 63, 64, 61, 59, 434, 62.00, 'Réintégration'],
        ['E008', 'Richard', 'Chloé', 'F12352', 'Relations Internationales', 2023, 90, 92, 88, 91, 89, 93, 90, 633, 90.43, 'Réussi'],
        ['E009', 'Moreau', 'Hugo', 'F12353', 'Relations Internationales', 2022, 50, 52, 48, 51, 49, 53, 50, 353, 50.43, 'Échec'],
        ['E010', 'Simon', 'Léa', 'F12354', 'Droit Public', 2023, 78, 80, 76, 79, 77, 81, 78, 549, 78.43, 'Réussi']
    ];
    
    foreach ($sampleStudents as $student) {
        // Calculate session 1 and session 2 grades (for example purposes)
        $s1_droit_civil = $student[6] - 5;
        $s1_procedure_penale = $student[7] - 5;
        $s1_contentieux_administratif = $student[8] - 5;
        $s1_securites_sociales = $student[9] - 5;
        $s1_voies_execution = $student[10] - 5;
        $s1_procedure_commerciale = $student[11] - 5;
        $s1_droits_humains = $student[12] - 5;
        
        $s2_droit_civil = $student[6] + 5;
        $s2_procedure_penale = $student[7] + 5;
        $s2_contentieux_administratif = $student[8] + 5;
        $s2_securites_sociales = $student[9] + 5;
        $s2_voies_execution = $student[10] + 5;
        $s2_procedure_commerciale = $student[11] + 5;
        $s2_droits_humains = $student[12] + 5;
        
        $total_s1 = $s1_droit_civil + $s1_procedure_penale + $s1_contentieux_administratif + 
                   $s1_securites_sociales + $s1_voies_execution + $s1_procedure_commerciale + $s1_droits_humains;
        $moyenne_s1 = $total_s1 / 7;
        
        $total_s2 = $s2_droit_civil + $s2_procedure_penale + $s2_contentieux_administratif + 
                   $s2_securites_sociales + $s2_voies_execution + $s2_procedure_commerciale + $s2_droits_humains;
        $moyenne_s2 = $total_s2 / 7;
        
        $sql = "INSERT INTO students (
            no_ordre, nom, prenom, no_fiche, section, année, 
            droit_civil, procedure_penale, contentieux_administratif, securites_sociales, 
            voies_execution, procedure_commerciale, droits_humains, total, moyenne, verdict,
            droit_civil_s1, procedure_penale_s1, contentieux_administratif_s1, securites_sociales_s1,
            voies_execution_s1, procedure_commerciale_s1, droits_humains_s1, total_s1, moyenne_s1,
            droit_civil_s2, procedure_penale_s2, contentieux_administratif_s2, securites_sociales_s2,
            voies_execution_s2, procedure_commerciale_s2, droits_humains_s2, total_s2, moyenne_s2
        ) VALUES (
            '{$student[0]}', '{$student[1]}', '{$student[2]}', '{$student[3]}', '{$student[4]}', {$student[5]}, 
            {$student[6]}, {$student[7]}, {$student[8]}, {$student[9]}, {$student[10]}, {$student[11]}, 
            {$student[12]}, {$student[13]}, {$student[14]}, '{$student[15]}',
            {$s1_droit_civil}, {$s1_procedure_penale}, {$s1_contentieux_administratif}, {$s1_securites_sociales},
            {$s1_voies_execution}, {$s1_procedure_commerciale}, {$s1_droits_humains}, {$total_s1}, {$moyenne_s1},
            {$s2_droit_civil}, {$s2_procedure_penale}, {$s2_contentieux_administratif}, {$s2_securites_sociales},
            {$s2_voies_execution}, {$s2_procedure_commerciale}, {$s2_droits_humains}, {$total_s2}, {$moyenne_s2}
        )";
        
        if ($conn->query($sql)) {
            // Successfully inserted
        } else {
            echo "Erreur lors de l'insertion d'un étudiant: " . $conn->error . "<br>";
        }
    }
    
    echo "Données d'exemple d'étudiants ajoutées avec succès<br>";
}

// Create directories for document libraries if they don't exist
if (!file_exists('tcpdf') && !file_exists('vendor/tecnickcom/tcpdf')) {
    echo "<div class='warning-message'>La bibliothèque TCPDF n'est pas installée. L'exportation PDF ne fonctionnera pas correctement.</div>";
    echo "Veuillez installer TCPDF en exécutant 'composer install' ou en téléchargeant la bibliothèque manuellement.<br>";
}

if (!file_exists('phpword') && !file_exists('vendor/phpoffice/phpword')) {
    echo "<div class='warning-message'>La bibliothèque PHPWord n'est pas installée. L'exportation Word ne fonctionnera pas correctement.</div>";
    echo "Veuillez installer PHPWord en exécutant 'composer install' ou en téléchargeant la bibliothèque manuellement.<br>";
}

$conn->close();

echo "<br>Configuration terminée!<br>";
echo "<a href='login.php'>Aller à la page de connexion</a>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        margin: 20px;
    }
    
    .warning-message {
        background-color: #fff3cd;
        color: #856404;
        padding: 10px;
        margin: 10px 0;
        border-radius: 4px;
        border: 1px solid #ffeeba;
    }
    
    a {
        display: inline-block;
        margin-top: 20px;
        padding: 10px 15px;
        background-color: #4a6fdc;
        color: white;
        text-decoration: none;
        border-radius: 4px;
    }
    
    a:hover {
        background-color: #3a5fc8;
    }
</style>