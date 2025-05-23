<?php
include_once('main.php');

$content = '
<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 text-center">
        <div>
            <h1 class="text-9xl font-bold text-blue-600">404</h1>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Page non trouvée
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                La page que vous recherchez n\'existe pas encore ou a été déplacée.
            </p>
        </div>
        <div class="mt-8">
            <a href="index.php" class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-home mr-2"></i>
                Retour à l\'accueil
            </a>
        </div>
    </div>
</div>';

// Inclure le template de mise en page
include('templates/layout.php');
?> 