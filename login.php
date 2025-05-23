<?php
$login_code = isset($_REQUEST['login']) ? $_REQUEST['login'] : '1';
$reset_success = isset($_REQUEST['reset']) ? $_REQUEST['reset'] : '';
$reset_error = isset($_REQUEST['error']) ? $_REQUEST['error'] : '';

if($login_code=="false"){
    $login_message = "Identifiants incorrects !";
    $login_type = "error";
} else {
    $login_message = "Veuillez vous connecter";
    $login_type = "info";
}

if(isset($_GET['error'])) {
    $error = $_GET['error'];
    $error_message = '';
    $student_name = isset($_GET['student_name']) ? htmlspecialchars($_GET['student_name']) : '';
    
    switch($error) {
        case 'student_not_found':
            $error_message = "L'étudiant n'a pas été trouvé dans la base de données.";
            break;
        case 'student_no_class':
            $error_message = "L'étudiant " . $student_name . " n'a pas de classe assignée. Veuillez contacter l'administrateur pour assigner une classe.";
            break;
        case 'student_class_not_found':
            $error_message = "La classe de l'étudiant n'a pas été trouvée. Veuillez contacter l'administrateur.";
            break;
        case 'login':
            $error_message = "Identifiant ou mot de passe incorrect.";
            break;
        default:
            $error_message = "Une erreur est survenue. Veuillez réessayer.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SchoolManager - Connexion</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-lg shadow-lg">
        <!-- Logo et Titre -->
        <div class="text-center">
            <a href="index.php" class="inline-block">
                <img src="source/logo.jpg" class="mx-auto h-24 w-24 object-contain" alt="Logo"/>
            </a>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Connexion
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Accédez à votre espace de gestion
            </p>
            
            <?php if($login_type == "error"): ?>
                <div class="mt-2 p-2 bg-red-100 border border-red-400 text-red-700 rounded">
                    <?php echo htmlspecialchars($login_message); ?>
                </div>
            <?php elseif($login_type == "info"): ?>
                <div class="mt-2 p-2 bg-blue-100 border border-blue-400 text-blue-700 rounded">
                    <?php echo htmlspecialchars($login_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if($reset_success): ?>
                <div class="mt-2 p-2 bg-green-100 border border-green-400 text-green-700 rounded">
                    Mot de passe modifié avec succès !
                </div>
            <?php endif; ?>
            
            <?php if($reset_error): ?>
                <div class="mt-2 p-2 bg-red-100 border border-red-400 text-red-700 rounded">
                    <?php echo htmlspecialchars($reset_error); ?>
                </div>
            <?php endif; ?>

            <?php if(isset($error_message) && $error_message): ?>
                <div class="mt-2 p-2 bg-red-100 border border-red-400 text-red-700 rounded">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Formulaire de connexion -->
        <form id="loginForm" class="mt-8 space-y-6" action="service/check.access.php" method="post">
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="myid" class="sr-only">Identifiant</label>
                    <input id="myid" name="myid" type="text" required 
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                        placeholder="Votre identifiant">
                </div>
                <div>
                    <label for="mypassword" class="sr-only">Mot de passe</label>
                    <input id="mypassword" name="mypassword" type="password" required 
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                        placeholder="Votre mot de passe">
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-sign-in-alt"></i>
                    </span>
                    Se connecter
                </button>
            </div>
            
            <div class="text-center space-y-2">
                <button type="button" onclick="showResetForm()" class="text-sm text-blue-600 hover:text-blue-500">
                    Changer mon mot de passe
                </button>
                <button type="button" onclick="showForgotForm()" class="text-sm text-blue-600 hover:text-blue-500 block">
                    Mot de passe oublié ?
                </button>
            </div>
        </form>

        <!-- Formulaire de changement de mot de passe -->
        <form id="resetForm" class="mt-8 space-y-6 hidden" action="service/reset_password.php" method="post">
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="reset_id" class="sr-only">Identifiant</label>
                    <input id="reset_id" name="user_id" type="text" required 
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                        placeholder="Votre identifiant">
                </div>
                <div>
                    <label for="current_password" class="sr-only">Mot de passe actuel</label>
                    <input id="current_password" name="current_password" type="password" required 
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                        placeholder="Mot de passe actuel">
                </div>
                <div>
                    <label for="new_password" class="sr-only">Nouveau mot de passe</label>
                    <input id="new_password" name="new_password" type="password" required 
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                        placeholder="Nouveau mot de passe">
                </div>
                <div>
                    <label for="confirm_password" class="sr-only">Confirmer le mot de passe</label>
                    <input id="confirm_password" name="confirm_password" type="password" required 
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                        placeholder="Confirmer le nouveau mot de passe">
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-key"></i>
                    </span>
                    Changer le mot de passe
                </button>
            </div>
            
            <div class="text-center">
                <button type="button" onclick="showLoginForm()" class="text-sm text-blue-600 hover:text-blue-500">
                    Retour à la connexion
                </button>
            </div>
        </form>

        <!-- Formulaire de mot de passe oublié -->
        <form id="forgotForm" class="mt-8 space-y-6 hidden" action="service/forgot_password.php" method="post">
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="forgot_id" class="sr-only">Identifiant</label>
                    <input id="forgot_id" name="user_id" type="text" required 
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                        placeholder="Votre identifiant">
                </div>
                <div>
                    <label for="email" class="sr-only">Email</label>
                    <input id="email" name="email" type="email" required 
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                        placeholder="Votre email">
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-key"></i>
                    </span>
                    Réinitialiser le mot de passe
                </button>
            </div>
            
            <div class="text-center">
                <button type="button" onclick="showLoginForm()" class="text-sm text-blue-600 hover:text-blue-500">
                    Retour à la connexion
                </button>
            </div>
        </form>

        <!-- Formulaire de réinitialisation avec code -->
        <form id="resetCodeForm" class="mt-8 space-y-6 hidden" action="service/reset_with_code.php" method="post">
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="reset_code" class="sr-only">Code de réinitialisation</label>
                    <input id="reset_code" name="reset_code" type="text" required 
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                        placeholder="Code reçu par email">
                </div>
                <div>
                    <label for="new_password_reset" class="sr-only">Nouveau mot de passe</label>
                    <input id="new_password_reset" name="new_password" type="password" required 
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                        placeholder="Nouveau mot de passe">
                </div>
                <div>
                    <label for="confirm_password_reset" class="sr-only">Confirmer le mot de passe</label>
                    <input id="confirm_password_reset" name="confirm_password" type="password" required 
                        class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                        placeholder="Confirmer le nouveau mot de passe">
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-check"></i>
                    </span>
                    Valider le nouveau mot de passe
                </button>
            </div>
            
            <div class="text-center">
                <button type="button" onclick="showLoginForm()" class="text-sm text-blue-600 hover:text-blue-500">
                    Retour à la connexion
                </button>
            </div>
        </form>

        <!-- Lien vers la page d'accueil -->
        <div class="text-center mt-4">
            <a href="index.php" class="text-sm text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-1"></i>
                Retour à la page d'accueil
            </a>
        </div>
    </div>

    <script>
    function showResetForm() {
        hideAllForms();
        document.getElementById('resetForm').classList.remove('hidden');
    }

    function showLoginForm() {
        hideAllForms();
        document.getElementById('loginForm').classList.remove('hidden');
    }

    function showForgotForm() {
        hideAllForms();
        document.getElementById('forgotForm').classList.remove('hidden');
    }

    function showResetCodeForm() {
        hideAllForms();
        document.getElementById('resetCodeForm').classList.remove('hidden');
    }

    function hideAllForms() {
        document.getElementById('loginForm').classList.add('hidden');
        document.getElementById('resetForm').classList.add('hidden');
        document.getElementById('forgotForm').classList.add('hidden');
        document.getElementById('resetCodeForm').classList.add('hidden');
    }

    // Validation des formulaires
    document.getElementById('resetForm').onsubmit = validatePasswordForm;
    document.getElementById('resetCodeForm').onsubmit = validatePasswordForm;

    function validatePasswordForm(e) {
        const form = e.target;
        const newPassword = form.querySelector('input[name="new_password"]').value;
        const confirmPassword = form.querySelector('input[name="confirm_password"]').value;

        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas !');
            return false;
        }

        if (newPassword.length < 8) {
            e.preventDefault();
            alert('Le mot de passe doit contenir au moins 8 caractères !');
            return false;
        }

        return true;
    }
    </script>
</body>
</html> 