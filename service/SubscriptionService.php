<?php
require_once __DIR__ . '/paydunya_service.php';
require_once __DIR__ . '/NotificationService.php';

class SubscriptionService {
    private $db;
    private $paydunya;
    private $notification;

    public function __construct($db) {
        $this->db = $db;
        $this->paydunya = new PayDunyaService($db);
        $this->notification = new NotificationService($db);
    }

    /**
     * Vérifie et gère les abonnements qui expirent bientôt
     */
    public function checkExpiringSubscriptions() {
        try {
            // Trouver les abonnements qui expirent dans les 7 jours
            $stmt = $this->db->prepare("
                SELECT s.*, u.id as user_id, u.email, u.username
                FROM subscriptions s
                JOIN users u ON u.school_id = s.id
                WHERE s.payment_status = 'completed'
                AND s.expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
                AND NOT EXISTS (
                    SELECT 1 FROM subscription_notifications n
                    WHERE n.subscription_id = s.id
                    AND n.type = 'expiry_warning'
                    AND n.sent_at > DATE_SUB(NOW(), INTERVAL 3 DAY)
                )
            ");
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($subscription = $result->fetch_assoc()) {
                // Créer une notification
                $this->createRenewalNotification(
                    $subscription['id'],
                    'expiry_warning',
                    "Votre abonnement expire le " . date('d/m/Y', strtotime($subscription['expiry_date'])) . 
                    ". Veuillez renouveler votre abonnement pour continuer à utiliser nos services."
                );

                // Envoyer un email
                $this->sendExpiryWarningEmail(
                    $subscription['email'],
                    $subscription['username'],
                    $subscription['school_name'],
                    $subscription['expiry_date']
                );

                // Créer une entrée de renouvellement
                $this->createRenewalEntry($subscription['id']);
            }
        } catch (Exception $e) {
            error_log("Erreur lors de la vérification des abonnements expirants : " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Gère les abonnements expirés
     */
    public function handleExpiredSubscriptions() {
        try {
            // Trouver les abonnements expirés
            $stmt = $this->db->prepare("
                UPDATE subscriptions s
                SET payment_status = 'expired'
                WHERE payment_status = 'completed'
                AND expiry_date < NOW()
            ");
            
            $stmt->execute();
            
            // Désactiver les comptes utilisateurs des abonnements expirés
            $stmt = $this->db->prepare("
                UPDATE users u
                JOIN subscriptions s ON u.school_id = s.id
                SET u.status = 'inactive'
                WHERE s.payment_status = 'expired'
                AND u.status = 'active'
            ");
            
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Erreur lors de la gestion des abonnements expirés : " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crée une nouvelle entrée de renouvellement
     */
    private function createRenewalEntry($subscription_id) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO subscription_renewals (
                    subscription_id, renewal_date, expiry_date, amount
                ) VALUES (
                    ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH), 15000.00
                )
            ");
            
            $stmt->bind_param("i", $subscription_id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Erreur lors de la création de l'entrée de renouvellement : " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crée une notification pour l'abonnement
     */
    private function createRenewalNotification($subscription_id, $type, $message) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO subscription_notifications (
                    subscription_id, type, message
                ) VALUES (?, ?, ?)
            ");
            
            $stmt->bind_param("iss", $subscription_id, $type, $message);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Erreur lors de la création de la notification : " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envoie un email d'avertissement d'expiration
     */
    private function sendExpiryWarningEmail($email, $username, $school_name, $expiry_date) {
        try {
            $mail = new PHPMailer(true);
            
            // Configuration SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'methndiaye43@gmail.com';
            $mail->Password = 'bela qfvl albr rjtd';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            
            // Destinataires
            $mail->setFrom('methndiaye43@gmail.com', 'SchoolManager');
            $mail->addAddress($email, $username);
            
            // Contenu
            $mail->isHTML(true);
            $mail->Subject = 'Votre abonnement SchoolManager expire bientôt';
            
            $renewal_url = "https://schoolmanager.sn/module/subscription/renew.php?school=" . urlencode($school_name);
            
            $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #4F46E5; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f9fafb; }
                    .button { display: inline-block; padding: 10px 20px; background: #4F46E5; color: white; text-decoration: none; border-radius: 5px; }
                    .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 0.9em; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Renouvellement d'abonnement</h1>
                    </div>
                    <div class='content'>
                        <p>Cher {$username},</p>
                        <p>Nous vous informons que votre abonnement pour <strong>{$school_name}</strong> expire le " . 
                        date('d/m/Y', strtotime($expiry_date)) . ".</p>
                        <p>Pour continuer à bénéficier de tous nos services, veuillez renouveler votre abonnement dès que possible.</p>
                        <p style='text-align: center; margin-top: 30px;'>
                            <a href='{$renewal_url}' class='button'>Renouveler mon abonnement</a>
                        </p>
                        <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>
                    </div>
                    <div class='footer'>
                        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                        <p>&copy; " . date('Y') . " SchoolManager. Tous droits réservés.</p>
                    </div>
                </div>
            </body>
            </html>";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Erreur lors de l'envoi de l'email d'expiration : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Traite le renouvellement d'un abonnement
     */
    public function processRenewal($subscription_id) {
        try {
            $this->db->begin_transaction();

            // Récupérer les informations de l'abonnement
            $stmt = $this->db->prepare("
                SELECT s.*, u.id as user_id, u.email, u.username
                FROM subscriptions s
                JOIN users u ON u.school_id = s.id
                WHERE s.id = ?
            ");
            
            $stmt->bind_param("i", $subscription_id);
            $stmt->execute();
            $subscription = $stmt->get_result()->fetch_assoc();

            if (!$subscription) {
                throw new Exception("Abonnement non trouvé");
            }

            // Créer le paiement via PayDunya
            $payment = $this->paydunya->createPayment([
                'id' => $subscription_id,
                'school_name' => $subscription['school_name'],
                'admin_email' => $subscription['email']
            ]);

            if (!$payment['success']) {
                throw new Exception("Erreur lors de la création du paiement");
            }

            // Mettre à jour l'entrée de renouvellement
            $stmt = $this->db->prepare("
                UPDATE subscription_renewals
                SET payment_reference = ?,
                    payment_status = 'pending',
                    updated_at = NOW()
                WHERE subscription_id = ?
                AND payment_status = 'pending'
                ORDER BY id DESC
                LIMIT 1
            ");
            
            $stmt->bind_param("si", $payment['token'], $subscription_id);
            $stmt->execute();

            $this->db->commit();
            return $payment;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Erreur lors du traitement du renouvellement : " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Confirme le renouvellement après paiement réussi
     */
    public function confirmRenewal($subscription_id, $payment_reference) {
        try {
            $this->db->begin_transaction();

            // Mettre à jour le statut de l'abonnement
            $stmt = $this->db->prepare("
                UPDATE subscriptions
                SET payment_status = 'completed',
                    expiry_date = DATE_ADD(expiry_date, INTERVAL 1 MONTH),
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->bind_param("i", $subscription_id);
            $stmt->execute();

            // Mettre à jour l'entrée de renouvellement
            $stmt = $this->db->prepare("
                UPDATE subscription_renewals
                SET payment_status = 'completed',
                    updated_at = NOW()
                WHERE subscription_id = ?
                AND payment_reference = ?
            ");
            
            $stmt->bind_param("is", $subscription_id, $payment_reference);
            $stmt->execute();

            // Réactiver le compte utilisateur si nécessaire
            $stmt = $this->db->prepare("
                UPDATE users
                SET status = 'active'
                WHERE school_id = ?
                AND status = 'inactive'
            ");
            
            $stmt->bind_param("i", $subscription_id);
            $stmt->execute();

            // Créer une notification de succès
            $this->createRenewalNotification(
                $subscription_id,
                'renewal_success',
                "Votre abonnement a été renouvelé avec succès jusqu'au " . 
                date('d/m/Y', strtotime('+1 month')) . "."
            );

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Erreur lors de la confirmation du renouvellement : " . $e->getMessage());
            throw $e;
        }
    }
} 