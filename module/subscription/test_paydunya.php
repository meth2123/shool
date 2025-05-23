<?php
require_once __DIR__ . '/../../service/mysqlcon.php';
require_once __DIR__ . '/../../service/paydunya_service.php';

// Fonction pour afficher les informations de manière formatée
function displayInfo($title, $data) {
    echo "<h3>$title</h3>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    echo "<hr>";
}

try {
    // Initialiser le service PayDunya
    $paydunya = new PayDunyaService($link);
    
    // Afficher les informations de configuration
    echo "<h2>Configuration PayDunya</h2>";
    echo "<p>Mode: " . ($paydunya->getMode() === 'test' ? 'Test' : 'Production') . "</p>";
    
    // Afficher les méthodes de paiement disponibles
    echo "<h3>Méthodes de Paiement</h3>";
    echo "<ul>";
    foreach ($paydunya->getPaymentMethods() as $method) {
        echo "<li>" . ucfirst(str_replace('-', ' ', $method)) . "</li>";
    }
    echo "</ul>";
    
    // Afficher les informations d'abonnement
    echo "<h3>Informations d'Abonnement</h3>";
    echo "<p>Montant: " . number_format($paydunya->getSubscriptionAmount(), 0, ',', ' ') . " FCFA</p>";
    echo "<p>Description: " . $paydunya->getSubscriptionDescription() . "</p>";
    
    // Créer un abonnement de test
    $test_subscription = [
        'id' => 1,
        'school_name' => 'École Test',
        'admin_email' => 'test@example.com',
        'admin_phone' => '+221XXXXXXXXX'
    ];
    
    try {
        // Tenter de créer un paiement
        echo "<h3>Test de Création de Paiement</h3>";
        $result = $paydunya->createPayment($test_subscription);
        
        if ($result['success']) {
            echo "<div style='color: green;'>";
            echo "<p>✅ Paiement créé avec succès!</p>";
            echo "<p>Token: " . htmlspecialchars($result['token']) . "</p>";
            echo "<p>URL de paiement: <a href='" . htmlspecialchars($result['invoice_url']) . "' target='_blank'>Cliquer ici pour payer</a></p>";
            echo "</div>";
        } else {
            echo "<div style='color: red;'>";
            echo "<p>❌ Erreur lors de la création du paiement</p>";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='color: red;'>";
        echo "<p>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin: 10px 0;'>";
    echo "❌ Erreur critique : " . $e->getMessage();
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Configuration PayDunya</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            background-color: #f8f9fa;
        }
        h2 {
            color: #4F46E5;
            border-bottom: 2px solid #4F46E5;
            padding-bottom: 10px;
        }
        h3 {
            color: #2d3748;
            margin-top: 20px;
        }
        pre {
            background-color: #f1f1f1;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        hr {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <!-- Le contenu PHP sera affiché ici -->
</body>
</html> 