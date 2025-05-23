<?php
include_once('mysqlcon.php');

// Charger la configuration SMTP
$smtp_config = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'methndiaye43@gmail.com',  // Votre email Gmail
    'password' => 'bela qfvl albr rjtd',     // Votre mot de passe d'application
    'from_email' => 'methndiaye43@gmail.com', // Même email que username
    'from_name' => 'Système de Gestion Scolaire'
];

// Vérifier si PHPMailer est installé
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once(__DIR__ . '/../vendor/autoload.php');
    $phpmailer_installed = true;
} else {
    $phpmailer_installed = false;
}

// Récupération des données du formulaire
$user_id = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Validation des données
if (empty($user_id) || empty($email)) {
    header("Location: ../?error=" . urlencode("Tous les champs sont obligatoires"));
    exit();
}

// Vérification de l'utilisateur et de son email dans toutes les tables
$sql = "SELECT u.userid, u.usertype,
        t.email as teacher_email,
        s.email as staff_email,
        st.email as student_email,
        p.fatherphone as parent_contact,
        a.email as admin_email
        FROM users u 
        LEFT JOIN teachers t ON u.userid = t.id 
        LEFT JOIN staff s ON u.userid = s.id
        LEFT JOIN students st ON u.userid = st.id
        LEFT JOIN parents p ON u.userid = p.id
        LEFT JOIN admin a ON u.userid = a.id
        WHERE u.userid = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: ../?error=" . urlencode("Identifiant invalide"));
    exit();
}

$user = $result->fetch_assoc();
// Récupérer l'email en fonction du type d'utilisateur
$stored_contact = null;
switch($user['usertype']) {
    case 'teacher':
        $stored_contact = $user['teacher_email'];
        break;
    case 'staff':
        $stored_contact = $user['staff_email'];
        break;
    case 'student':
        $stored_contact = $user['student_email'];
        break;
    case 'parent':
        $stored_contact = $user['parent_contact'];
        break;
    case 'admin':
        $stored_contact = $user['admin_email'];
        break;
}

// Vérification du contact
if (empty($stored_contact)) {
    header("Location: ../?error=" . urlencode("Aucun contact trouvé pour cet utilisateur"));
    exit();
}

// Pour les parents, on compare avec le numéro de téléphone
if ($user['usertype'] === 'parent') {
    if ($stored_contact !== $email) {
        header("Location: ../?error=" . urlencode("Le numéro de téléphone ne correspond pas à cet identifiant"));
        exit();
    }
} else {
    // Pour les autres utilisateurs, on compare avec l'email
    if (strtolower($stored_contact) !== strtolower($email)) {
        header("Location: ../?error=" . urlencode("L'email ne correspond pas à cet identifiant"));
        exit();
    }
}

// Génération d'un code de réinitialisation unique
$reset_code = bin2hex(random_bytes(16));
$expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Stockage du code dans la base de données
$sql = "INSERT INTO password_resets (user_id, reset_code, expiry) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE reset_code = VALUES(reset_code), expiry = VALUES(expiry), used = 0";
$stmt = $link->prepare($sql);
$stmt->bind_param("sss", $user_id, $reset_code, $expiry);

if (!$stmt->execute()) {
    header("Location: ../?error=" . urlencode("Erreur lors de la génération du code de réinitialisation"));
    exit();
}

if (!$phpmailer_installed) {
    // Message temporaire en attendant l'installation de PHPMailer
    header("Location: ../?error=" . urlencode("Le système d'envoi d'email n'est pas encore configuré. Veuillez contacter l'administrateur avec le code suivant : " . $reset_code));
    exit();
}

// Si PHPMailer est installé, on continue avec l'envoi de l'email
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $mail = new PHPMailer(true);
    
    // Configuration du serveur
    $mail->isSMTP();
    $mail->Host = $smtp_config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_config['username'];
    $mail->Password = $smtp_config['password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtp_config['port'];
    $mail->CharSet = 'UTF-8';
    
    // Destinataires
    $mail->setFrom($smtp_config['from_email'], $smtp_config['from_name']);
    
    // Déterminer l'adresse email en fonction du type d'utilisateur
    $recipient_email = '';
    if ($user['usertype'] === 'parent') {
        // Pour les parents, on utilise le numéro de téléphone comme identifiant
        $recipient_email = $stored_contact . '@sms.parent'; // Format spécial pour les parents
    } else {
        $recipient_email = $stored_contact;
    }
    
    $mail->addAddress($recipient_email);
    
    // Contenu
    $mail->isHTML(true);
    $mail->Subject = 'Réinitialisation de votre mot de passe';
    
    // Message personnalisé selon le type d'utilisateur
    if ($user['usertype'] === 'parent') {
        $mail->Body = "
            <h2>Réinitialisation de votre mot de passe</h2>
            <p>Cher parent,</p>
            <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
            <p>Voici votre code de réinitialisation : <strong>{$reset_code}</strong></p>
            <p>Ce code expirera dans 1 heure.</p>
            <p>Si vous n'avez pas demandé cette réinitialisation, veuillez ignorer ce message.</p>
            <p>Cordialement,<br>L'équipe de gestion scolaire</p>
        ";
    } else {
        $mail->Body = "
            <h2>Réinitialisation de votre mot de passe</h2>
            <p>Bonjour,</p>
            <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
            <p>Voici votre code de réinitialisation : <strong>{$reset_code}</strong></p>
            <p>Ce code expirera dans 1 heure.</p>
            <p>Si vous n'avez pas demandé cette réinitialisation, veuillez ignorer cet email.</p>
            <p>Cordialement,<br>L'équipe de gestion scolaire</p>
        ";
    }
    
    // Version texte pour les clients mail qui ne supportent pas le HTML
    $mail->AltBody = "Code de réinitialisation : {$reset_code}\nCe code expirera dans 1 heure.";
    
    $mail->send();
    
    // Redirection vers la page de réinitialisation avec le code
    header("Location: reset_password.php?code=" . urlencode($reset_code) . "&user_id=" . urlencode($user_id));
    
} catch (Exception $e) {
    error_log("Erreur d'envoi d'email: " . $mail->ErrorInfo);
    
    // En cas d'erreur, rediriger vers la page de réinitialisation avec le code
    header("Location: reset_password.php?code=" . urlencode($reset_code) . "&user_id=" . urlencode($user_id));
}

exit();
?> 