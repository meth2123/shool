<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
require_once __DIR__ . '/../../service/mysqlcon.php';
require_once __DIR__ . '/../../service/NotificationService.php';

// Vérifier si PHPMailer est installé
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once(__DIR__ . '/../../vendor/autoload.php');
}

// Vérifier si l'utilisateur est connecté, est un administrateur et est spécifiquement ad-123-1
if (!isset($_SESSION['user_type']) || 
    $_SESSION['user_type'] !== 'admin' || 
    !isset($_SESSION['user_id']) || 
    $_SESSION['user_id'] !== 'ad-123-1') {
    header("Location: ../../index.php?error=unauthorized");
    exit();
}

$error_message = '';
$success_message = '';
$subscriptions = [];
$renewals = [];
$notifications = [];

// Configuration SMTP
$smtp_config = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'methndiaye43@gmail.com',
    'password' => 'bela qfvl albr rjtd',
    'from_email' => 'methndiaye43@gmail.com',
    'from_name' => 'SchoolManager'
];

// Fonction pour générer un ID admin unique
function generateAdminId($school_name) {
    global $link;
    $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $school_name), 0, 3));
    do {
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $admin_id = $prefix . $random;
        
        // Vérifier si l'ID existe déjà
        $stmt = $link->prepare("SELECT id FROM admin WHERE id = ?");
        $stmt->bind_param("s", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } while ($result->num_rows > 0);
    
    return $admin_id;
}

// Fonction pour générer un mot de passe sécurisé
function generatePassword() {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < 12; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

// Traitement du changement de statut manuel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    try {
        $subscription_id = $_POST['subscription_id'];
        $new_status = $_POST['new_status'];
        
        // Mettre à jour le statut
        $stmt = $link->prepare("UPDATE subscriptions SET payment_status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $new_status, $subscription_id);
        $stmt->execute();

        // Si le statut est passé à "completed", créer une notification
        if ($new_status === 'completed') {
            try {
                $link->begin_transaction();

                // Récupérer les informations de l'abonnement
                $stmt = $link->prepare("SELECT * FROM subscriptions WHERE id = ?");
                $stmt->bind_param("i", $subscription_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $subscription = $result->fetch_assoc();

                // Vérifier si un compte admin existe déjà pour cette école
                $stmt = $link->prepare("SELECT id FROM admin WHERE email = ?");
                $stmt->bind_param("s", $subscription['admin_email']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    // Générer l'ID admin et le mot de passe
                    $admin_id = generateAdminId($subscription['school_name']);
                    $password = generatePassword();
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Créer le compte admin avec les champs requis
                    $stmt = $link->prepare("
                        INSERT INTO admin (
                            id, name, password, phone, email, 
                            dob, hiredate, address, sex
                        ) VALUES (?, ?, ?, ?, ?, CURDATE(), CURDATE(), ?, 'male')
                    ");
                    
                    $stmt->bind_param(
                        "ssssss",
                        $admin_id,
                        $subscription['director_name'],
                        $hashed_password,
                        $subscription['admin_phone'],
                        $subscription['admin_email'],
                        $subscription['school_name'] // Utiliser le nom de l'école comme adresse
                    );
                    $stmt->execute();

                    // Créer l'entrée dans la table users
                    $stmt = $link->prepare("
                        INSERT INTO users (
                            userid, password, usertype
                        ) VALUES (?, ?, 'admin')
                    ");
                    
                    $stmt->bind_param(
                        "ss",
                        $admin_id,
                        $hashed_password
                    );
                    $stmt->execute();

                    // Créer une notification avec les paramètres corrects
                    $notificationService = new NotificationService(
                        $link,
                        $_SESSION['user_id'],
                        $_SESSION['user_type']
                    );

                    // Créer la notification pour l'administrateur de l'école
                    $notificationService->create(
                        "Compte administrateur créé",
                        "Votre compte administrateur a été créé avec succès. Utilisez les identifiants fournis dans l'email pour vous connecter.",
                        $subscription['admin_email'],
                        'admin',
                        'success'
                    );

                    // Envoyer un email avec les identifiants
                    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
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
                            $mail->addAddress($subscription['admin_email']);
                            
                            // Contenu
                            $mail->isHTML(true);
                            $mail->Subject = "Vos identifiants SchoolManager - " . $subscription['school_name'];
                            
                            $login_url = "https://schoolmanager.sn/login.php";
                            
                            $mail->Body = "
                                <html>
                                <head>
                                    <style>
                                        body { font-family: Arial, sans-serif; line-height: 1.6; }
                                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                        .header { background: #4F46E5; color: white; padding: 20px; text-align: center; }
                                        .content { padding: 20px; background: #f9fafb; }
                                        .credentials { background: #e5e7eb; padding: 15px; border-radius: 5px; margin: 20px 0; }
                                        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 0.9em; }
                                        .button { display: inline-block; padding: 10px 20px; background: #4F46E5; color: white; text-decoration: none; border-radius: 5px; }
                                    </style>
                                </head>
                                <body>
                                    <div class='container'>
                                        <div class='header'>
                                            <h1>Bienvenue sur SchoolManager</h1>
                                        </div>
                                        <div class='content'>
                                            <p>Cher administrateur,</p>
                                            <p>Votre compte pour <strong>{$subscription['school_name']}</strong> a été créé avec succès.</p>
                                            <p>Voici vos identifiants de connexion :</p>
                                            <div class='credentials'>
                                                <p><strong>Identifiant :</strong> {$admin_id}</p>
                                                <p><strong>Mot de passe :</strong> {$password}</p>
                                            </div>
                                            <p><strong>Important :</strong> Pour des raisons de sécurité, veuillez modifier votre mot de passe lors de votre première connexion.</p>
                                            <p style='text-align: center; margin-top: 30px;'>
                                                <a href='{$login_url}' class='button'>Accéder à mon espace</a>
                                            </p>
                                        </div>
                                        <div class='footer'>
                                            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                                            <p>&copy; " . date('Y') . " SchoolManager. Tous droits réservés.</p>
                                        </div>
                                    </div>
                                </body>
                                </html>
                            ";

                            // Version texte pour les clients mail qui ne supportent pas le HTML
                            $mail->AltBody = "
                                Bienvenue sur SchoolManager

                                Cher administrateur,

                                Votre compte pour {$subscription['school_name']} a été créé avec succès.

                                Voici vos identifiants de connexion :
                                Identifiant : {$admin_id}
                                Mot de passe : {$password}

                                Important : Pour des raisons de sécurité, veuillez modifier votre mot de passe lors de votre première connexion.

                                Vous pouvez vous connecter à votre espace administrateur en visitant : {$login_url}

                                Cet email a été envoyé automatiquement, merci de ne pas y répondre.
                                © " . date('Y') . " SchoolManager. Tous droits réservés.
                            ";

                            $mail->send();
                            $success_message = "Le statut a été mis à jour, le compte administrateur a été créé et les identifiants ont été envoyés par email.";
                        } catch (Exception $e) {
                            error_log("Erreur lors de l'envoi de l'email : " . $mail->ErrorInfo);
                            $success_message = "Le compte administrateur a été créé, mais l'envoi de l'email a échoué : " . $mail->ErrorInfo;
                        }
                    } else {
                        $success_message = "Le compte administrateur a été créé, mais PHPMailer n'est pas installé. Impossible d'envoyer l'email avec les identifiants.";
                    }
                } else {
                    // Si le compte existe déjà, juste mettre à jour le statut
                    $success_message = "Le statut a été mis à jour. Le compte administrateur existe déjà.";
                }

                $link->commit();
            } catch (Exception $e) {
                $link->rollback();
                $error_message = "Erreur lors de la mise à jour : " . $e->getMessage();
            }
        } else {
            $success_message = "Le statut a été mis à jour.";
        }
    } catch (Exception $e) {
        $error_message = "Erreur lors de la mise à jour du statut : " . $e->getMessage();
    }
}

try {
    // Récupérer le nom de l'administrateur connecté
    $stmt = $link->prepare("SELECT name FROM admin WHERE id = ?");
    $stmt->bind_param("s", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $admin_name = $admin['name'] ?? 'Administrateur';

    // Récupérer tous les abonnements avec leurs détails
    $query = "
        SELECT 
            s.*,
            a.id as admin_id,
            a.name as admin_name,
            a.email as admin_email,
            COUNT(r.id) as renewal_count,
            MAX(r.renewal_date) as last_renewal,
            MAX(n.sent_at) as last_notification
        FROM subscriptions s
        LEFT JOIN admin a ON CAST(a.email AS CHAR) = CAST(s.admin_email AS CHAR)
        LEFT JOIN subscription_renewals r ON CAST(r.subscription_id AS CHAR) = CAST(s.id AS CHAR)
        LEFT JOIN subscription_notifications n ON CAST(n.subscription_id AS CHAR) = CAST(s.id AS CHAR)
        GROUP BY s.id
        ORDER BY s.expiry_date DESC
    ";
    
    $result = $link->query($query);
    while ($row = $result->fetch_assoc()) {
        $subscriptions[] = $row;
    }

    // Récupérer les renouvellements récents
    $query = "
        SELECT 
            r.*,
            s.school_name,
            s.payment_status as subscription_status
        FROM subscription_renewals r
        JOIN subscriptions s ON CAST(s.id AS CHAR) = CAST(r.subscription_id AS CHAR)
        ORDER BY r.created_at DESC
        LIMIT 10
    ";
    
    $result = $link->query($query);
    while ($row = $result->fetch_assoc()) {
        $renewals[] = $row;
    }

    // Récupérer les notifications récentes
    $query = "
        SELECT 
            n.*,
            s.school_name
        FROM subscription_notifications n
        JOIN subscriptions s ON CAST(s.id AS CHAR) = CAST(n.subscription_id AS CHAR)
        ORDER BY n.sent_at DESC
        LIMIT 10
    ";
    
    $result = $link->query($query);
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

} catch (Exception $e) {
    $error_message = "Erreur lors de la récupération des données : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification des Abonnements - SchoolManager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Vérification des Abonnements</h1>
                <p class="mt-2 text-sm text-gray-600">Surveillez l'état des abonnements et des renouvellements</p>
            </div>

            <?php if ($error_message): ?>
                <div class="rounded-md bg-red-50 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                <?php echo htmlspecialchars($error_message); ?>
                            </h3>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="rounded-md bg-green-50 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">
                                <?php echo htmlspecialchars($success_message); ?>
                            </h3>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Liste des abonnements -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
                <div class="px-4 py-5 sm:px-6">
                    <h2 class="text-lg font-medium text-gray-900">État des Abonnements</h2>
                </div>
                <div class="border-t border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">École</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Méthode de paiement</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Renouvellements</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dernière notification</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($subscriptions as $sub): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($sub['school_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($sub['admin_name'] ?? $sub['admin_id'] ?? '-'); ?><br>
                                        <span class="text-xs"><?php echo htmlspecialchars($sub['admin_email'] ?? '-'); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $status_class = [
                                            'completed' => 'bg-green-100 text-green-800',
                                            'expired' => 'bg-red-100 text-red-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'failed' => 'bg-red-100 text-red-800'
                                        ][$sub['payment_status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                <?php echo ucfirst($sub['payment_status']); ?>
                                            </span>
                                            <?php if ($sub['payment_status'] !== 'completed'): ?>
                                                <form method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir marquer ce paiement comme complété ?');">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="subscription_id" value="<?php echo $sub['id']; ?>">
                                                    <input type="hidden" name="new_status" value="completed">
                                                    <button type="submit" class="text-xs bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded">
                                                        <i class="fas fa-check"></i> Valider
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $payment_method = strtolower($sub['payment_method'] ?? '');
                                        $method_class = [
                                            'orange money' => 'bg-orange-100 text-orange-800',
                                            'free money' => 'bg-blue-100 text-blue-800',
                                            'wave' => 'bg-purple-100 text-purple-800'
                                        ][$payment_method] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $method_class; ?>">
                                            <?php 
                                            switch($payment_method) {
                                                case 'orange money':
                                                    echo '<i class="fas fa-mobile-alt mr-1"></i> Orange Money';
                                                    break;
                                                case 'free money':
                                                    echo '<i class="fas fa-money-bill-wave mr-1"></i> Free Money';
                                                    break;
                                                case 'wave':
                                                    echo '<i class="fas fa-wave-square mr-1"></i> Wave';
                                                    break;
                                                default:
                                                    echo 'Non spécifié';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('d/m/Y', strtotime($sub['expiry_date'])); ?>
                                        <?php if (strtotime($sub['expiry_date']) < time()): ?>
                                            <span class="text-red-600">(Expiré)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $sub['renewal_count']; ?>
                                        <?php if ($sub['last_renewal']): ?>
                                            <br>
                                            <span class="text-xs">Dernier: <?php echo date('d/m/Y', strtotime($sub['last_renewal'])); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($sub['last_notification']): ?>
                                            <?php echo date('d/m/Y H:i', strtotime($sub['last_notification'])); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Renouvellements récents -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
                <div class="px-4 py-5 sm:px-6">
                    <h2 class="text-lg font-medium text-gray-900">Renouvellements Récents</h2>
                </div>
                <div class="border-t border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">École</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Méthode</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Référence</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($renewals as $renewal): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($renewal['school_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('d/m/Y H:i', strtotime($renewal['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo number_format($renewal['amount'], 0, ',', ' '); ?> FCFA
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $payment_method = strtolower($renewal['payment_method'] ?? '');
                                        $method_class = [
                                            'orange money' => 'bg-orange-100 text-orange-800',
                                            'free money' => 'bg-blue-100 text-blue-800',
                                            'wave' => 'bg-purple-100 text-purple-800'
                                        ][$payment_method] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $method_class; ?>">
                                            <?php 
                                            switch($payment_method) {
                                                case 'orange money':
                                                    echo '<i class="fas fa-mobile-alt mr-1"></i> Orange Money';
                                                    break;
                                                case 'free money':
                                                    echo '<i class="fas fa-money-bill-wave mr-1"></i> Free Money';
                                                    break;
                                                case 'wave':
                                                    echo '<i class="fas fa-wave-square mr-1"></i> Wave';
                                                    break;
                                                default:
                                                    echo 'Non spécifié';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $status_class = [
                                            'completed' => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'failed' => 'bg-red-100 text-red-800',
                                            'expired' => 'bg-red-100 text-red-800'
                                        ][$renewal['payment_status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                            <?php echo ucfirst($renewal['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($renewal['payment_reference'] ?? '-'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Notifications récentes -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h2 class="text-lg font-medium text-gray-900">Notifications Récentes</h2>
                </div>
                <div class="border-t border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">École</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lu</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($notifications as $notif): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($notif['school_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $type_class = [
                                            'expiry_warning' => 'bg-yellow-100 text-yellow-800',
                                            'payment_failed' => 'bg-red-100 text-red-800',
                                            'renewal_success' => 'bg-green-100 text-green-800',
                                            'renewal_failed' => 'bg-red-100 text-red-800'
                                        ][$notif['type']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $type_class; ?>">
                                            <?php echo str_replace('_', ' ', ucfirst($notif['type'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($notif['message']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('d/m/Y H:i', strtotime($notif['sent_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($notif['read_at']): ?>
                                            <span class="text-green-600"><?php echo date('d/m/Y H:i', strtotime($notif['read_at'])); ?></span>
                                        <?php else: ?>
                                            <span class="text-red-600">Non lu</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 