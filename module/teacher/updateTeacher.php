<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification de la session
if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Définir la variable check pour le template layout.php
$check = $_SESSION['login_id'];

// Récupération des informations de l'enseignant
$teacher = db_fetch_row(
    "SELECT * FROM teachers WHERE id = ?",
    [$check],
    's'
);

if (!$teacher) {
    $content = '<div class="alert alert-danger" role="alert">
                <h4 class="alert-heading">Erreur!</h4>
                <p>Enseignant non trouvé.</p>
              </div>';
    include('templates/layout.php');
    exit();
}

// Traitement du formulaire
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $teaId = $_POST['id'];
    $teaPassword = $_POST['password'];
    $teaPhone = $_POST['phone'];
    $teaEmail = $_POST['email'];
    $teaAddress = $_POST['address'];
    
    // Validation des données
    $errors = [];
    
    if (empty($teaPassword)) {
        $errors[] = "Le mot de passe ne peut pas être vide";
    }
    
    if (empty($teaPhone)) {
        $errors[] = "Le numéro de téléphone ne peut pas être vide";
    }
    
    if (empty($teaEmail)) {
        $errors[] = "L'email ne peut pas être vide";
    } elseif (!filter_var($teaEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format d'email invalide";
    }
    
    if (empty($errors)) {
        try {
            // Mise à jour des informations de l'enseignant
            $update_result = db_query(
                "UPDATE teachers SET password = ?, phone = ?, email = ?, address = ? WHERE id = ?",
                [$teaPassword, $teaPhone, $teaEmail, $teaAddress, $teaId],
                'sssss'
            );
            
            // Mise à jour du mot de passe dans la table users
            $update_user_result = db_query(
                "UPDATE users SET password = ? WHERE userid = ?",
                [$teaPassword, $teaId],
                'ss'
            );
            
            if ($update_result && $update_user_result) {
                $success_message = "Vos informations ont été mises à jour avec succès.";
                
                // Rafraîchir les données de l'enseignant
                $teacher = db_fetch_row(
                    "SELECT * FROM teachers WHERE id = ?",
                    [$check],
                    's'
                );
            } else {
                $error_message = "Une erreur est survenue lors de la mise à jour des données.";
            }
        } catch (Exception $e) {
            $error_message = "Erreur: " . $e->getMessage();
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Préparation du contenu pour le template
$content = '';

// Affichage des messages de succès/erreur
if (!empty($success_message)) {
    $content .= '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    ' . htmlspecialchars($success_message) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
}

if (!empty($error_message)) {
    $content .= '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    ' . $error_message . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
}

// Formulaire de mise à jour
$content .= '<div class="card">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">Modifier mon profil</h5>
    </div>
    <div class="card-body">
        <form method="post" action="" class="needs-validation" novalidate>
            <div class="row g-3">
                <!-- ID -->
                <div class="col-md-6">
                    <label for="id" class="form-label">ID</label>
                    <input type="text" class="form-control bg-light" id="id" name="id" 
                        value="' . htmlspecialchars($teacher['id']) . '" readonly>
                </div>

                <!-- Nom -->
                <div class="col-md-6">
                    <label for="name" class="form-label">Nom</label>
                    <input type="text" class="form-control bg-light" id="name" name="name" 
                        value="' . htmlspecialchars($teacher['name']) . '" readonly>
                </div>

                <!-- Mot de passe -->
                <div class="col-md-6">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="password" name="password" 
                        value="' . htmlspecialchars($teacher['password']) . '" required>
                    <div class="form-text">Vous pouvez modifier votre mot de passe ici.</div>
                </div>

                <!-- Téléphone -->
                <div class="col-md-6">
                    <label for="phone" class="form-label">Téléphone</label>
                    <input type="tel" class="form-control" id="phone" name="phone" 
                        value="' . htmlspecialchars($teacher['phone']) . '" required>
                </div>

                <!-- Email -->
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                        value="' . htmlspecialchars($teacher['email']) . '" required>
                </div>

                <!-- Genre -->
                <div class="col-md-6">
                    <label for="gender" class="form-label">Genre</label>
                    <input type="text" class="form-control bg-light" id="gender" name="gender" 
                        value="' . htmlspecialchars($teacher['sex']) . '" readonly>
                </div>

                <!-- Date de naissance -->
                <div class="col-md-6">
                    <label for="dob" class="form-label">Date de naissance</label>
                    <input type="text" class="form-control bg-light" id="dob" name="dob" 
                        value="' . htmlspecialchars($teacher['dob']) . '" readonly>
                </div>

                <!-- Adresse -->
                <div class="col-md-6">
                    <label for="address" class="form-label">Adresse</label>
                    <input type="text" class="form-control" id="address" name="address" 
                        value="' . htmlspecialchars($teacher['address']) . '" required>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" name="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>';

// Inclure le template
include('templates/layout.php');
?>
