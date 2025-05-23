<?php
require_once '../../service/paydunya_service.php';
require_once '../../service/paydunya_env.php';
require_once '../../service/db_utils.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation des données du formulaire
    $school_name = trim($_POST['school_name'] ?? '');
    $director_name = trim($_POST['director_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($school_name) || empty($director_name) || empty($email) || empty($phone)) {
        $error_message = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "L'adresse email n'est pas valide.";
    } else {
        try {
            // Utilisation de la connexion globale $link
            global $link;
            if (!$link) {
                throw new Exception("Erreur de connexion à la base de données");
            }
            
            // Vérifier si les colonnes existent
            $result = $link->query("SHOW COLUMNS FROM subscriptions LIKE 'director_name'");
            $has_director_name = $result->num_rows > 0;
            
            $result = $link->query("SHOW COLUMNS FROM subscriptions LIKE 'address'");
            $has_address = $result->num_rows > 0;
            
            // Préparer la requête en fonction des colonnes disponibles
            if ($has_director_name && $has_address) {
                $sql = "INSERT INTO subscriptions (
                    school_name, director_name, admin_email, admin_phone, 
                    address, payment_status, created_at
                ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
                $stmt = $link->prepare($sql);
                $stmt->bind_param("sssss", $school_name, $director_name, $email, $phone, $address);
            } else {
                $sql = "INSERT INTO subscriptions (
                    school_name, admin_email, admin_phone, 
                    payment_status, created_at
                ) VALUES (?, ?, ?, 'pending', NOW())";
                $stmt = $link->prepare($sql);
                $stmt->bind_param("sss", $school_name, $email, $phone);
            }
            
            if (!$stmt) {
                throw new Exception("Erreur de préparation de la requête : " . $link->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Erreur lors de la création de l'abonnement : " . $stmt->error);
            }
            
            $subscription_id = $link->insert_id;
            
            // Instanciation de PayDunyaService avec la connexion
            $paydunyaService = new PayDunyaService($link);
            
            // Créer le paiement avec les informations de l'abonnement
            $result = $paydunyaService->createPayment([
                'id' => $subscription_id,
                'school_name' => $school_name,
                'admin_email' => $email
            ]);

            if ($result['success']) {
                header("Location: " . $result['invoice_url']);
                exit;
            } else {
                $error_message = "Une erreur est survenue lors de la création du paiement. Veuillez réessayer.";
            }
        } catch (Exception $e) {
            $error_message = "Une erreur est survenue : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SchoolManager - Inscription</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="../../index.php" class="flex items-center">
                        <img src="../../source/logo.jpg" class="h-8 w-8 object-contain" alt="Logo"/>
                        <span class="ml-2 text-xl font-bold text-gray-900">SchoolManager</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../../index.php#features" class="text-gray-600 hover:text-gray-900">Fonctionnalités</a>
                    <a href="../../index.php#pricing" class="text-gray-600 hover:text-gray-900">Tarifs</a>
                    <a href="../../login.php" class="text-blue-600 hover:text-blue-700">Se connecter</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Formulaire d'inscription -->
    <div class="max-w-2xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-8 sm:p-10">
                <div class="text-center">
                    <h2 class="text-3xl font-extrabold text-gray-900">
                        Inscription à SchoolManager
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Remplissez le formulaire ci-dessous pour créer votre compte et accéder à toutes les fonctionnalités.
                    </p>
                </div>

                <?php if ($error_message): ?>
                    <div class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <form class="mt-8 space-y-6" action="" method="POST">
                    <div class="space-y-4">
                        <!-- Nom de l'établissement -->
                        <div>
                            <label for="school_name" class="block text-sm font-medium text-gray-700">
                                Nom de l'établissement <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="school_name" name="school_name" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="Ex: Lycée Moderne de Dakar">
                        </div>

                        <!-- Nom du directeur -->
                        <div>
                            <label for="director_name" class="block text-sm font-medium text-gray-700">
                                Nom du directeur <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="director_name" name="director_name" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="Ex: Mamadou Diop">
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" id="email" name="email" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="exemple@ecole.sn">
                        </div>

                        <!-- Téléphone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">
                                Téléphone <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" id="phone" name="phone" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="Ex: +221 77 123 45 67">
                        </div>

                        <!-- Adresse -->
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700">
                                Adresse
                            </label>
                            <textarea id="address" name="address" rows="3"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="Adresse complète de l'établissement"></textarea>
                        </div>
                    </div>

                    <!-- Informations sur le paiement -->
                    <div class="mt-6 bg-gray-50 p-4 rounded-md">
                        <h3 class="text-lg font-medium text-gray-900">Informations sur l'abonnement</h3>
                        <div class="mt-2 text-sm text-gray-600">
                            <p>Prix de l'abonnement : <span class="font-bold">15 000 FCFA / mois</span></p>
                            <p class="mt-1">Le paiement sera effectué via PayDunya, notre partenaire de paiement sécurisé.</p>
                            <ul class="mt-2 list-disc list-inside space-y-1">
                                <li>Paiement sécurisé</li>
                                <li>Support Orange Money, Wave, Visa, Mastercard</li>
                                <li>Facture disponible immédiatement</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-crown mr-2"></i>
                            Procéder au paiement
                        </button>
                    </div>
                </form>

                <!-- Lien vers la page d'accueil -->
                <div class="mt-6 text-center">
                    <a href="../../index.php" class="text-sm text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left mr-1"></i>
                        Retour à la page d'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 mt-12">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-base text-gray-400">
                    &copy; <?php echo date('Y'); ?> SchoolManager. Tous droits réservés.
                </p>
            </div>
        </div>
    </footer>
</body>
</html> 