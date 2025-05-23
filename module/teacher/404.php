<?php
include_once('main.php');

// Initialiser le contenu pour le template
ob_start();
?>

<div class="min-h-screen bg-gray-100 flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
        <!-- Icône 404 -->
        <div class="mb-6">
            <i class="fas fa-exclamation-circle text-6xl text-blue-500"></i>
        </div>
        
        <!-- Titre -->
        <h1 class="text-6xl font-bold text-blue-600 mb-4">404</h1>
        
        <!-- Message -->
        <p class="text-xl text-gray-600 mb-8">
            La page que vous recherchez n'existe pas ou a été déplacée.
        </p>
        
        <!-- Bouton de retour -->
        <a href="index.php" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
            <i class="fas fa-home mr-2"></i>
            Retour à l'accueil
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
include('templates/layout.php');
?> 