<?php
session_start();
require_once __DIR__ . '/../../service/mysqlcon.php';
require_once __DIR__ . '/../../service/SubscriptionService.php';

$error_message = '';
$success_message = '';
$subscription = null;

// Vérifier si l'école est spécifiée
if (isset($_GET['school'])) {
    $school_name = urldecode($_GET['school']);
    
    try {
        // Récupérer les informations de l'abonnement
        $stmt = $link->prepare("
            SELECT s.*, u.id as user_id, u.email, u.username
            FROM subscriptions s
            JOIN users u ON u.school_id = s.id
            WHERE s.school_name = ?
            AND u.role = 'admin'
            LIMIT 1
        ");
        
        $stmt->bind_param("s", $school_name);
        $stmt->execute();
        $subscription = $stmt->get_result()->fetch_assoc();
        
        if (!$subscription) {
            throw new Exception("Abonnement non trouvé");
        }
        
        // Vérifier si l'abonnement peut être renouvelé
        if ($subscription['payment_status'] === 'completed' && 
            strtotime($subscription['expiry_date']) > strtotime('+7 days')) {
            $error_message = "Votre abonnement est encore valide jusqu'au " . 
                            date('d/m/Y', strtotime($subscription['expiry_date']));
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Traiter le renouvellement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['renew']) && $subscription) {
    try {
        $subscriptionService = new SubscriptionService($link);
        $payment = $subscriptionService->processRenewal($subscription['id']);
        
        if ($payment['success']) {
            header("Location: " . $payment['invoice_url']);
            exit;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renouvellement d'abonnement - SchoolManager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <img class="mx-auto h-12 w-auto" src="../../source/logo.jpg" alt="SchoolManager">
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Renouvellement d'abonnement
                </h2>
            </div>

            <?php if ($error_message): ?>
                <div class="rounded-md bg-red-50 p-4">
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

            <?php if ($subscription): ?>
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Informations de l'abonnement
                        </h3>
                    </div>
                    <div class="border-t border-gray-200">
                        <dl>
                            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">
                                    École
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    <?php echo htmlspecialchars($subscription['school_name']); ?>
                                </dd>
                            </div>
                            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">
                                    Date d'expiration
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    <?php echo date('d/m/Y', strtotime($subscription['expiry_date'])); ?>
                                </dd>
                            </div>
                            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">
                                    Statut
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    <?php
                                    switch ($subscription['payment_status']) {
                                        case 'completed':
                                            echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Actif</span>';
                                            break;
                                        case 'expired':
                                            echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Expiré</span>';
                                            break;
                                        case 'pending':
                                            echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">En attente</span>';
                                            break;
                                        default:
                                            echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Inconnu</span>';
                                    }
                                    ?>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <?php if ($subscription['payment_status'] !== 'completed' || 
                         strtotime($subscription['expiry_date']) <= strtotime('+7 days')): ?>
                    <form method="POST" class="mt-8 space-y-6">
                        <div class="rounded-md bg-blue-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-400"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">
                                        Montant du renouvellement : 15 000 FCFA
                                    </h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <p>Le paiement sera effectué via PayDunya, notre partenaire de paiement sécurisé.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <button type="submit" name="renew" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                    <i class="fas fa-crown text-green-500 group-hover:text-green-400"></i>
                                </span>
                                Renouveler mon abonnement
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <div class="rounded-md bg-yellow-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                Aucun abonnement trouvé
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>Veuillez vérifier le nom de l'école ou contacter le support.</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="text-center">
                <a href="../../index.php" class="text-sm text-gray-600 hover:text-gray-900">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Retour à la page d'accueil
                </a>
            </div>
        </div>
    </div>
</body>
</html> 