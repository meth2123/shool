<?php
// Configuration de l'environnement PayDunya pour Render.com
// Les variables d'environnement peuvent être configurées dans le dashboard Render.com
// sous Settings > Environment Variables

// Récupération des variables d'environnement si définies sur Render.com
$base_url = getenv('APP_URL') ?: 'https://schoolmanager.sn';
$master_key = getenv('PAYDUNYA_MASTER_KEY') ?: 'J8Bk1t8t-AWZp-kVD1-WbjB-CndDy4hrVS7J';
$public_key = getenv('PAYDUNYA_PUBLIC_KEY') ?: 'live_public_sSLcfppVXgj8EPvJejPaJQ3p577';
$private_key = getenv('PAYDUNYA_PRIVATE_KEY') ?: 'live_private_c79m7kcs9viYYMKyDXTHPwLfjk0';
$token = getenv('PAYDUNYA_TOKEN') ?: 'DIjlzayBLdsFdtqYXZ2v';

// Définir les URLs de callback
$callback_url = $base_url . '/module/subscription/callback.php';
$cancel_url = $base_url . '/module/subscription/cancel.php';
$return_url = $base_url . '/module/subscription/success.php';

return [
    'mode' => 'live', // Mode production
    'store' => [
        'name' => 'SchoolManager',
        'tagline' => 'Système de Gestion Scolaire',
        'postal_address' => 'Dakar, Sénégal',
        'phone_number' => '+221 77 123 45 67',
        'website_url' => $base_url,
        'logo_url' => $base_url . '/source/logo.jpg',
        'callback_url' => $callback_url,
        'cancel_url' => $cancel_url,
        'return_url' => $return_url
    ],
    'api_keys' => [
        'master_key' => $master_key,
        'public_key' => $public_key,
        'private_key' => $private_key,
        'token' => $token
    ],
    'payment_methods' => [
        'orange-money' => true,
        'wave' => true,
        'visa' => true,
        'mastercard' => true
    ],
    'subscription' => [
        'amount' => 15000.00, // 15 000 FCFA
        'description' => 'Abonnement mensuel à SchoolManager - Système de Gestion Scolaire'
    ]
];

// Log de la configuration
error_log("Configuration PayDunya chargée - Mode: Production");
error_log("Base URL: " . $base_url);
error_log("Callback URL: " . $callback_url);

// Vérification de la sécurité
if (strpos($base_url, 'https://') !== 0) {
    error_log("ATTENTION: L'URL de base doit utiliser HTTPS pour PayDunya");
} 