<?php
// Déclarations use en premier
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensuite les require
require_once __DIR__ . '/paydunya_sdk.php';
require_once __DIR__ . '/paydunya_env.php';

// Vérifier si PHPMailer est installé
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once(__DIR__ . '/../vendor/autoload.php');
}

class PayDunyaService {
    private $config;
    private $sdk;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->config = require __DIR__ . '/paydunya_env.php';
        $this->sdk = new PayDunyaSDK($this->config);
        
        error_log("PayDunya Service initialisé - Environnement: " . ($this->config['mode'] === 'test' ? 'Test' : 'Production'));
        error_log("Base URL: " . $this->config['store']['website_url']);
    }

    public function getMode() {
        return $this->config['mode'];
    }

    public function createPayment($subscription) {
        try {
            // Préparer les données de la facture
            $invoice_data = [
                'items' => [
                    [
                        'name' => 'Abonnement SchoolManager',
                        'quantity' => 1,
                        'unit_price' => $this->config['subscription']['amount'],
                        'total_price' => $this->config['subscription']['amount'],
                        'description' => $this->config['subscription']['description']
                    ]
                ],
                'total_amount' => $this->config['subscription']['amount'],
                'description' => $this->config['subscription']['description'],
                'custom_data' => [
                    'subscription_id' => $subscription['id'],
                    'school_name' => $subscription['school_name'],
                    'admin_email' => $subscription['admin_email']
                ]
            ];

            // Créer la facture via le SDK
            $result = $this->sdk->createInvoice($invoice_data);

            if ($result['success']) {
                // Mettre à jour la base de données avec la référence de paiement
                $stmt = $this->db->prepare("
                    UPDATE subscriptions 
                    SET payment_reference = ?, 
                        payment_status = 'pending',
                        updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->bind_param("si", $result['token'], $subscription['id']);
                
                if (!$stmt->execute()) {
                    throw new Exception("Erreur lors de la mise à jour de la base de données");
                }

                return [
                    'success' => true,
                    'invoice_url' => $result['invoice_url'],
                    'token' => $result['token']
                ];
            }

            throw new Exception("Erreur lors de la création de la facture PayDunya");

        } catch (Exception $e) {
            error_log("Erreur PayDunya: " . $e->getMessage());
            throw $e;
        }
    }

    public function getPaymentMethods() {
        return array_keys(array_filter($this->config['payment_methods'], function($enabled) {
            return $enabled === true;
        }));
    }

    public function getSubscriptionAmount() {
        return $this->config['subscription']['amount'];
    }

    public function getSubscriptionDescription() {
        return $this->config['subscription']['description'];
    }

    public function handleCallback($payload) {
        try {
            $data = json_decode($payload, true);
            if (!isset($data['token'])) {
                throw new Exception("Token manquant dans le payload");
            }

            // Confirmer la facture
            $result = $this->sdk->confirmInvoice($data['token']);
            
            if ($result['status'] === "completed") {
                $this->db->begin_transaction();

                try {
                    $subscription_id = $result['custom_data']['subscription_id'] ?? null;
                    if (!$subscription_id) {
                        throw new Exception("ID d'abonnement manquant");
                    }

                    // Mettre à jour le statut de paiement
                    $stmt = $this->db->prepare("
                        UPDATE subscriptions 
                        SET payment_status = 'completed', 
                            payment_date = NOW(),
                            receipt_url = ?
                        WHERE id = ? AND payment_status = 'pending'
                    ");
                    $stmt->bind_param("si", $result['receipt_url'], $subscription_id);
                    $stmt->execute();

                    if ($stmt->affected_rows === 0) {
                        throw new Exception("Abonnement non trouvé ou déjà payé");
                    }

                    // Récupérer les informations de l'abonnement
                    $stmt = $this->db->prepare("SELECT * FROM subscriptions WHERE id = ?");
                    $stmt->bind_param("i", $subscription_id);
                    $stmt->execute();
                    $subscription = $stmt->get_result()->fetch_assoc();

                    // Générer l'ID admin et le mot de passe
                    $admin_id = $this->generateAdminId($subscription['school_name']);
                    $password = $this->generatePassword();

                    // Créer le compte admin
                    $stmt = $this->db->prepare("
                        INSERT INTO users (
                            username, password, email, phone, role, 
                            school_id, created_at, status
                        ) VALUES (?, ?, ?, ?, 'admin', ?, NOW(), 'active')
                    ");
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt->bind_param(
                        "ssssi", 
                        $admin_id, 
                        $hashed_password, 
                        $subscription['admin_email'],
                        $subscription['admin_phone'],
                        $subscription['id']
                    );
                    $stmt->execute();

                    // Envoyer les identifiants par email
                    $this->sendCredentialsEmail(
                        $subscription['admin_email'],
                        $admin_id,
                        $password,
                        $subscription['school_name']
                    );

                    $this->db->commit();
                    return true;

                } catch (Exception $e) {
                    $this->db->rollback();
                    throw $e;
                }
            }

            return false;

        } catch (Exception $e) {
            error_log("Erreur callback PayDunya: " . $e->getMessage());
            throw $e;
        }
    }

    private function generateAdminId($school_name) {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $school_name), 0, 3));
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . $random;
    }

    private function generatePassword() {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = '';
        for ($i = 0; $i < 12; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

    private function sendCredentialsEmail($email, $admin_id, $password, $school_name) {
        // Vérifier si PHPMailer est installé
        if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
            error_log("PHPMailer n'est pas installé. Impossible d'envoyer l'email.");
            return false;
        }

        // Configuration SMTP
        $smtp_config = [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => 'methndiaye43@gmail.com',
            'password' => 'bela qfvl albr rjtd',
            'from_email' => 'methndiaye43@gmail.com',
            'from_name' => 'SchoolManager'
        ];

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
            $mail->addAddress($email);
            
            // Contenu
            $mail->isHTML(true);
            $mail->Subject = 'Vos identifiants SchoolManager';
            
            // URL de connexion
            $login_url = $this->config['store']['website_url'] . '/login.php';
            
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
                        <p>Votre abonnement pour <strong>{$school_name}</strong> a été activé avec succès.</p>
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
            </html>";

            // Version texte pour les clients mail qui ne supportent pas le HTML
            $mail->AltBody = "
            Bienvenue sur SchoolManager

            Cher administrateur,

            Votre abonnement pour {$school_name} a été activé avec succès.

            Voici vos identifiants de connexion :
            Identifiant : {$admin_id}
            Mot de passe : {$password}

            Important : Pour des raisons de sécurité, veuillez modifier votre mot de passe lors de votre première connexion.

            Vous pouvez vous connecter à votre espace administrateur en visitant : {$login_url}

            Cet email a été envoyé automatiquement, merci de ne pas y répondre.
            © " . date('Y') . " SchoolManager. Tous droits réservés.";

            $mail->send();
            error_log("Email de confirmation envoyé avec succès à {$email}");
            return true;

        } catch (Exception $e) {
            error_log("Erreur lors de l'envoi de l'email de confirmation : " . $mail->ErrorInfo);
            return false;
        }
    }
} 