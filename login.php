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
        case 'account_inactive':
            $status = isset($_GET['status']) ? $_GET['status'] : '';
            
            switch($status) {
                case 'pending':
                    $error_message = "Votre paiement est en attente de confirmation. Veuillez réessayer ultérieurement ou contacter le support.";
                    break;
                case 'failed':
                    $error_message = "Votre dernier paiement a échoué. Veuillez renouveler votre abonnement pour accéder à votre compte.";
                    break;
                case 'expired':
                    $error_message = "Votre abonnement a expiré. Veuillez le renouveler pour continuer à utiliser nos services.";
                    break;
                default:
                    $error_message = "Votre compte a été désactivé. Veuillez contacter l'administrateur ou renouveler votre abonnement.";
            }
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 2rem;
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            border-color: #86b7fe;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .btn-success {
            background-color: #198754;
            border-color: #198754;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Logo et Titre -->
        <div class="text-center mb-4">
            <a href="index.php" class="d-inline-block">
                <img src="source/logo.jpg" class="img-fluid" style="max-height: 100px;" alt="Logo"/>
            </a>
            <h2 class="mt-3 fw-bold">
                Connexion
            </h2>
            <p class="text-muted small">
                Accédez à votre espace de gestion
            </p>
            
            <?php if($login_type == "error"): ?>
                <div class="alert alert-danger mt-3" role="alert">
                    <?php echo htmlspecialchars($login_message); ?>
                </div>
            <?php elseif($login_type == "info"): ?>
                <div class="alert alert-info mt-3" role="alert">
                    <?php echo htmlspecialchars($login_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if($reset_success): ?>
                <div class="alert alert-success mt-3" role="alert">
                    Mot de passe modifié avec succès !
                </div>
            <?php endif; ?>
            
            <?php if($reset_error): ?>
                <div class="alert alert-danger mt-3" role="alert">
                    <?php echo htmlspecialchars($reset_error); ?>
                </div>
            <?php endif; ?>

            <?php if(isset($error_message) && $error_message): ?>
                <div class="alert alert-danger mt-3" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Formulaire de connexion -->
        <form id="loginForm" action="service/check.access.php" method="post" class="mb-3">
            <div class="mb-3">
                <label for="myid" class="form-label">Identifiant</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input id="myid" name="myid" type="text" class="form-control" placeholder="Votre identifiant" required>
                </div>
            </div>
            <div class="mb-4">
                <label for="mypassword" class="form-label">Mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input id="mypassword" name="mypassword" type="password" class="form-control" placeholder="Votre mot de passe" required>
                </div>
            </div>

            <div class="d-grid gap-2 mb-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                </button>
            </div>
            
            <div class="text-center">
                <button type="button" onclick="showForgotForm()" class="btn btn-link p-0 text-decoration-none">
                    Mot de passe oublié ?
                </button>
            </div>
        </form>

        <!-- Formulaire de changement de mot de passe -->
        <form id="resetForm" class="d-none mb-3" action="service/reset_password.php" method="post">
            <div class="mb-3">
                <label for="reset_id" class="form-label">Identifiant</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input id="reset_id" name="user_id" type="text" class="form-control" placeholder="Votre identifiant" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="current_password" class="form-label">Mot de passe actuel</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input id="current_password" name="current_password" type="password" class="form-control" placeholder="Mot de passe actuel" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label">Nouveau mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                    <input id="new_password" name="new_password" type="password" class="form-control" placeholder="Nouveau mot de passe" required>
                </div>
            </div>
            <div class="mb-4">
                <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-check"></i></span>
                    <input id="confirm_password" name="confirm_password" type="password" class="form-control" placeholder="Confirmer le nouveau mot de passe" required>
                </div>
            </div>

            <div class="d-grid gap-2 mb-3">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check me-2"></i>Changer mon mot de passe
                </button>
            </div>
            
            <div class="text-center">
                <button type="button" onclick="showLoginForm()" class="btn btn-link p-0 text-decoration-none">
                    Retour à la connexion
                </button>
            </div>
        </form>

        <!-- Formulaire de mot de passe oublié -->
        <form id="forgotForm" class="d-none mb-3" action="service/forgot_password.php" method="post">
            <div class="mb-3">
                <label for="forgot_id" class="form-label">Identifiant</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input id="forgot_id" name="user_id" type="text" class="form-control" placeholder="Votre identifiant" required>
                </div>
            </div>
            <div class="mb-4">
                <label for="forgot_email" class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input id="forgot_email" name="email" type="email" class="form-control" placeholder="Votre email" required>
                </div>
            </div>

            <div class="d-grid gap-2 mb-3">
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-paper-plane me-2"></i>Envoyer le code de réinitialisation
                </button>
            </div>
            
            <div class="text-center">
                <button type="button" onclick="showLoginForm()" class="btn btn-link p-0 text-decoration-none">
                    Retour à la connexion
                </button>
            </div>
        </form>

        <!-- Formulaire de réinitialisation avec code -->
        <form id="resetCodeForm" class="d-none mb-3" action="service/reset_with_code.php" method="post">
            <div class="mb-3">
                <label for="reset_code_id" class="form-label">Identifiant</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input id="reset_code_id" name="user_id" type="text" class="form-control" placeholder="Votre identifiant" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="reset_code" class="form-label">Code de réinitialisation</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                    <input id="reset_code" name="reset_code" type="text" class="form-control" placeholder="Code de réinitialisation" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="new_password_reset" class="form-label">Nouveau mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input id="new_password_reset" name="new_password" type="password" class="form-control" placeholder="Nouveau mot de passe" required>
                </div>
            </div>
            <div class="mb-4">
                <label for="confirm_password_reset" class="form-label">Confirmer le mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-check"></i></span>
                    <input id="confirm_password_reset" name="confirm_password" type="password" class="form-control" placeholder="Confirmer le nouveau mot de passe" required>
                </div>
            </div>

            <div class="d-grid gap-2 mb-3">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check me-2"></i>Valider le nouveau mot de passe
                </button>
            </div>
            
            <div class="text-center">
                <button type="button" onclick="showLoginForm()" class="btn btn-link p-0 text-decoration-none">
                    Retour à la connexion
                </button>
            </div>
        </form>

        <!-- Lien vers la page d'accueil -->
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>
                Retour à la page d'accueil
            </a>
        </div>
    </div>

    <!-- Bootstrap JS et script personnalisé -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function showResetForm() {
        hideAllForms();
        document.getElementById('resetForm').classList.remove('d-none');
    }

    function showLoginForm() {
        hideAllForms();
        document.getElementById('loginForm').classList.remove('d-none');
    }

    function showForgotForm() {
        hideAllForms();
        document.getElementById('forgotForm').classList.remove('d-none');
    }

    function showResetCodeForm() {
        hideAllForms();
        document.getElementById('resetCodeForm').classList.remove('d-none');
    }

    function hideAllForms() {
        document.getElementById('loginForm').classList.add('d-none');
        document.getElementById('resetForm').classList.add('d-none');
        document.getElementById('forgotForm').classList.add('d-none');
        document.getElementById('resetCodeForm').classList.add('d-none');
    }

    // Validation des formulaires avec Bootstrap
    document.getElementById('resetForm').onsubmit = validatePasswordForm;
    document.getElementById('resetCodeForm').onsubmit = validatePasswordForm;

    function validatePasswordForm(e) {
        const form = e.target;
        const newPassword = form.querySelector('input[name="new_password"]').value;
        const confirmPassword = form.querySelector('input[name="confirm_password"]').value;

        if (newPassword !== confirmPassword) {
            e.preventDefault();
            showValidationError(form, 'Les mots de passe ne correspondent pas !');
            return false;
        }

        if (newPassword.length < 8) {
            e.preventDefault();
            showValidationError(form, 'Le mot de passe doit contenir au moins 8 caractères !');
            return false;
        }

        return true;
    }
    
    function showValidationError(form, message) {
        // Créer une alerte Bootstrap
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger mt-3';
        alertDiv.role = 'alert';
        alertDiv.textContent = message;
        
        // Supprimer les alertes précédentes
        const existingAlerts = form.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());
        
        // Ajouter la nouvelle alerte au début du formulaire
        form.prepend(alertDiv);
        
        // Faire défiler jusqu'à l'alerte
        alertDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    </script>
</body>
</html> 