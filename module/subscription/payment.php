<?php
session_start();
require_once __DIR__ . '/../../service/mysqlcon.php';
require_once __DIR__ . '/../../service/paydunya_service.php';

// Vérifier si la référence est fournie
if (!isset($_GET['ref'])) {
    header('Location: register.php');
    exit;
}

$reference = $_GET['ref'];

try {
    // Récupérer les informations de l'abonnement
    $stmt = $link->prepare("SELECT * FROM subscriptions WHERE reference = ?");
    $stmt->bind_param("s", $reference);
    $stmt->execute();
    $result = $stmt->get_result();
    $subscription = $result->fetch_assoc();

    if (!$subscription) {
        throw new Exception("Abonnement non trouvé");
    }

    // Initialiser le service PayDunya
    $paydunya = new PayDunyaService($link);
    $payment = $paydunya->createPayment($subscription);

    if ($payment['success']) {
        // Rediriger vers la page de paiement PayDunya
        header("Location: " . $payment['invoice_url']);
        exit;
    }

    throw new Exception("Erreur lors de l'initialisation du paiement");

} catch (Exception $e) {
    $_SESSION['error'] = "Erreur lors de l'initialisation du paiement : " . $e->getMessage();
    header("Location: register.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialisation du paiement - Gestion Scolaire</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full mx-4">
            <div class="text-center mb-8">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                <h2 class="mt-4 text-xl font-semibold text-gray-700">
                    Initialisation du paiement en cours...
                </h2>
                <p class="mt-2 text-gray-600">
                    Vous allez être redirigé vers la page de paiement sécurisée.
                </p>
            </div>
        </div>
    </div>

    <script>
        // Redirection automatique après 3 secondes si JavaScript est activé
        setTimeout(function() {
            window.location.href = '<?php echo $payment['invoice_url'] ?? 'register.php'; ?>';
        }, 3000);
    </script>
</body>
</html> 