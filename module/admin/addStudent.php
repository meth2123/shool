<?php
// Inclure les fichiers nécessaires
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Debug: Vérifier si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Méthode POST détectée");
    error_log("Données POST reçues : " . print_r($_POST, true));
}

// Vérifier la connexion à la base de données
if (!isset($link) || !$link) {
    error_log("Erreur : Pas de connexion à la base de données");
    die("Erreur de connexion à la base de données");
}

$check = $_SESSION['login_id'];
$admin_name = $loged_user_name;

// Récupérer la liste des classes avec mysqli
$classes = [];
$sql = "SELECT * FROM class WHERE created_by = ? OR created_by = '21' ORDER BY name, section";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $check);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
}

// Créer la table admin_actions si elle n'existe pas
createTableIfNotExists($link);

// S'assurer que la colonne created_by existe
addCreatedByColumnIfNotExists($link, 'students');

$content = <<<CONTENT
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="card-title text-center mb-0">Inscription d'un Nouvel Étudiant</h4>
                </div>
                <div class="card-body">
                    <form action="#" method="post" onsubmit="return validateStudentForm();" enctype="multipart/form-data">
                        <input type="hidden" name="created_by" value="{$check}">
                        
                        <!-- ID et Nom -->
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="stuId" class="form-label">ID Étudiant*</label>
                                <input id="stuId" type="text" name="studentId" placeholder="Entrer l'ID" required
                                       class="form-control">
                                <div id="stuIdError" class="invalid-feedback d-none"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="stuName" class="form-label">Nom Complet*</label>
                                <input id="stuName" type="text" name="studentName" placeholder="Entrer le nom" required
                                       class="form-control">
                                <div id="stuNameError" class="invalid-feedback d-none"></div>
                            </div>
                        </div>

                        <!-- Mot de passe et Téléphone -->
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="stuPassword" class="form-label">Mot de passe*</label>
                                <input id="stuPassword" type="password" name="studentPassword" placeholder="Entrer le mot de passe" required
                                       class="form-control">
                                <div id="stuPasswordError" class="invalid-feedback d-none"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="stuPhone" class="form-label">Téléphone*</label>
                                <input id="stuPhone" type="tel" name="studentPhone" placeholder="Entrer le numéro" required
                                       class="form-control">
                                <div id="stuPhoneError" class="invalid-feedback d-none"></div>
                            </div>
                        </div>

                        <!-- Email et Genre -->
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="stuEmail" class="form-label">Email*</label>
                                <input id="stuEmail" type="email" name="studentEmail" placeholder="Entrer l'email" required
                                       class="form-control">
                                <div id="stuEmailError" class="invalid-feedback d-none"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Genre*</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="genderMale" value="Male">
                                        <label class="form-check-label" for="genderMale">
                                            Masculin
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="Female">
                                        <label class="form-check-label" for="genderFemale">
                                            Féminin
                                        </label>
                                    </div>
                                </div>
                                <div id="genderError" class="invalid-feedback d-none"></div>
                            </div>
                        </div>

                        <!-- Date de naissance et Date d'admission -->
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="stuDOB" class="form-label">Date de naissance*</label>
                                <input id="stuDOB" type="date" name="studentDOB" required
                                       class="form-control">
                                <div id="stuDOBError" class="invalid-feedback d-none"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="stuAddmissionDate" class="form-label">Date d'admission</label>
                                <input id="stuAddmissionDate" type="date" name="studentAddmissionDate" value="<?php echo date('Y-m-d'); ?>" readonly 
                                       class="form-control bg-light">
                            </div>
                        </div>
                        <!-- Adresse -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="stuAddress" class="form-label">Adresse*</label>
                                <textarea id="stuAddress" name="studentAddress" rows="3" placeholder="Entrer l'adresse" required
                                          class="form-control"></textarea>
                                <div id="stuAddressError" class="invalid-feedback d-none"></div>
                            </div>
                        </div>

                        <!-- ID Parent et Classe -->
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="stuParentId" class="form-label">ID Parent*</label>
                                <input id="stuParentId" type="text" name="studentParentId" placeholder="Entrer l'ID du parent" required
                                       class="form-control">
                                <div id="stuParentIdError" class="invalid-feedback d-none"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="stuClassId" class="form-label">Classe*</label>
                                <select id="stuClassId" name="studentClassId" required class="form-select">
                                    <option value="">Sélectionner une classe</option>
CONTENT;

foreach($classes as $class) {
    $content .= <<<CONTENT
                            <option value="{$class['id']}">
                                {$class['name']} - {$class['section']}
                            </option>
CONTENT;
}

$content .= <<<CONTENT
                                </select>
                                <div id="stuClassIdError" class="invalid-feedback d-none"></div>
                            </div>
                        </div>

                        <!-- Photo -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label for="file" class="form-label">Photo de l'étudiant</label>
                                <input id="file" type="file" name="file" accept="image/*" class="form-control">
                                <div id="fileError" class="invalid-feedback d-none"></div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="row">
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" name="submit" value="1" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Inscrire l'étudiant
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Message de succès ou d'erreur -->
            <div id="message" class="mt-4"></div>
        </div>
    </div>

<script>
function validateStudentForm() {
    let isValid = true;
    const errors = {};
    
    // Réinitialiser tous les messages d'erreur
    document.querySelectorAll('.invalid-feedback').forEach(el => {
        el.classList.add('d-none');
        el.textContent = '';
    });
    
    document.querySelectorAll('.form-control, .form-select, .form-check-input').forEach(el => {
        el.classList.remove('is-invalid');
    });

    // Validate Student ID
    const stuId = document.getElementById('stuId').value;
    if (!stuId || !/^[A-Za-z0-9]+$/.test(stuId)) {
        showError('stuId', "L'ID étudiant est requis et ne doit contenir que des lettres et des chiffres");
        isValid = false;
    }

    // Validate Name
    const stuName = document.getElementById('stuName').value;
    if (!stuName || stuName.length < 2) {
        showError('stuName', "Le nom doit contenir au moins 2 caractères");
        isValid = false;
    }

    // Validate Password
    const stuPassword = document.getElementById('stuPassword').value;
    if (!stuPassword || stuPassword.length < 6) {
        showError('stuPassword', "Le mot de passe doit contenir au moins 6 caractères");
        isValid = false;
    }

    // Validate Phone
    const stuPhone = document.getElementById('stuPhone').value;
    if (!stuPhone || !/^[0-9+\-\s]+$/.test(stuPhone)) {
        showError('stuPhone', "Le numéro de téléphone est requis et doit être valide");
        isValid = false;
    }

    // Validate Email
    const stuEmail = document.getElementById('stuEmail').value;
    if (!stuEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(stuEmail)) {
        showError('stuEmail', "L'email est requis et doit être valide");
        isValid = false;
    }

    // Validate Gender
    const gender = document.querySelector('input[name="gender"]:checked');
    if (!gender) {
        document.getElementById('genderError').textContent = "Veuillez sélectionner un genre";
        document.getElementById('genderError').classList.remove('d-none');
        document.querySelectorAll('input[name="gender"]').forEach(el => {
            el.classList.add('is-invalid');
        });
        isValid = false;
    }
    
    // Validate DOB
    const stuDOB = document.getElementById('stuDOB').value;
    if (!stuDOB) {
        showError('stuDOB', "La date de naissance est requise");
        isValid = false;
    }
    
    // Validate Address
    const stuAddress = document.getElementById('stuAddress').value;
    if (!stuAddress || stuAddress.length < 5) {
        showError('stuAddress', "L'adresse est requise et doit contenir au moins 5 caractères");
        isValid = false;
    }
    
    // Validate Parent ID
    const stuParentId = document.getElementById('stuParentId').value;
    if (!stuParentId) {
        showError('stuParentId', "L'ID du parent est requis");
        isValid = false;
    }
    
    // Validate Class
    const stuClassId = document.getElementById('stuClassId').value;
    if (!stuClassId) {
        showError('stuClassId', "Veuillez sélectionner une classe");
        isValid = false;
    }
    
    return isValid;
}

// Fonction pour afficher les erreurs
function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorElement = document.getElementById(fieldId + 'Error');
    
    if (field && errorElement) {
        field.classList.add('is-invalid');
        errorElement.textContent = message;
        errorElement.classList.remove('d-none');
    }
}

// Cette fonction n'est plus nécessaire car nous utilisons showError directement
// Gardée pour référence en cas de besoin futur
/*
function displayErrors(errors) {
    Object.keys(errors).forEach(field => {
        const errorElement = document.getElementById(field + 'Error');
        if (errorElement) {
            errorElement.textContent = errors[field];
            errorElement.classList.remove('d-none');
            document.getElementById(field).classList.add('is-invalid');
        }
    });
}
*/
</script>
CONTENT;

include('templates/layout.php');

// Traitement du formulaire
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['studentId'])){
    error_log("Début du traitement du formulaire");
    try {
        // Vérifier la connexion à la base de données
        if (!$link) {
            error_log("ERREUR: Pas de connexion à la base de données");
            throw new Exception("La connexion à la base de données n'est pas établie");
        }
        error_log("Connexion à la base de données OK");

        // Valider et nettoyer les données
        $stuId = trim($_POST['studentId']);
        $stuName = trim($_POST['studentName']);
        $plainPassword = $_POST['studentPassword'];
        $stuPassword = password_hash($plainPassword, PASSWORD_DEFAULT); // Hasher le mot de passe
        $stuPhone = trim($_POST['studentPhone']);
        $stuEmail = filter_var(trim($_POST['studentEmail']), FILTER_VALIDATE_EMAIL);
    $stugender = $_POST['gender'];
    $stuDOB = $_POST['studentDOB'];
        $stuAddmissionDate = date('Y-m-d');
        $stuAddress = trim($_POST['studentAddress']);
        $stuParentId = trim($_POST['studentParentId']);
        $stuClassId = trim($_POST['studentClassId']);
        $admin_id = $_SESSION['login_id'];

        error_log("Données reçues:");
        error_log("ID: " . $stuId);
        error_log("Nom: " . $stuName);
        error_log("Email: " . $stuEmail);
        error_log("Genre: " . $stugender);
        error_log("Date de naissance: " . $stuDOB);
        error_log("Classe: " . $stuClassId);
        error_log("Admin ID: " . $admin_id);

        // Validation des données
        if (empty($stuId) || empty($stuName) || empty($plainPassword)) {
            error_log("ERREUR: Champs requis manquants");
            throw new Exception("L'ID, le nom et le mot de passe sont obligatoires");
        }

        // Vérifier si l'ID existe déjà
        $check_sql = "SELECT userid FROM users WHERE userid = ?";
        $check_stmt = $link->prepare($check_sql);
        if (!$check_stmt) {
            error_log("ERREUR préparation requête vérification: " . $link->error);
            throw new Exception("Erreur lors de la vérification de l'ID");
        }

        $check_stmt->bind_param("s", $stuId);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            error_log("ERREUR: ID étudiant déjà utilisé");
            throw new Exception("Cet ID étudiant existe déjà");
        }

        // Vérifier si la classe existe
        $check_class = "SELECT id FROM class WHERE id = ?";
        $class_stmt = $link->prepare($check_class);
        if (!$class_stmt) {
            error_log("ERREUR préparation requête classe: " . $link->error);
            throw new Exception("Erreur lors de la vérification de la classe");
        }

        error_log("Vérification de la classe: " . $stuClassId);
        $class_stmt->bind_param("s", $stuClassId);
        $class_stmt->execute();
        $class_result = $class_stmt->get_result();
        error_log("Nombre de classes trouvées: " . $class_result->num_rows);
        
        if ($class_result->num_rows === 0) {
            error_log("ERREUR: Classe inexistante: " . $stuClassId);
            throw new Exception("La classe spécifiée n'existe pas");
        }

        // Vérifier si le parent existe
        $check_parent = "SELECT id FROM parents WHERE id = ?";
        $parent_stmt = $link->prepare($check_parent);
        if (!$parent_stmt) {
            error_log("ERREUR préparation requête parent: " . $link->error);
            throw new Exception("Erreur lors de la vérification du parent");
        }

        error_log("Vérification du parent: " . $stuParentId);
        $parent_stmt->bind_param("s", $stuParentId);
        $parent_stmt->execute();
        $parent_result = $parent_stmt->get_result();
        error_log("Nombre de parents trouvés: " . $parent_result->num_rows);
        
        if ($parent_result->num_rows === 0) {
            error_log("ERREUR: Parent inexistant: " . $stuParentId);
            throw new Exception("L'ID du parent spécifié n'existe pas");
        }

        // Début de la transaction
        error_log("Début de la transaction");
        $link->begin_transaction();

        try {
            // Insertion dans la table users
            $sql = "INSERT INTO users (userid, password, usertype) VALUES (?, ?, 'student')";
            $stmt = $link->prepare($sql);
            if (!$stmt) {
                error_log("ERREUR préparation requête users: " . $link->error);
                throw new Exception("Erreur lors de la préparation de l'insertion utilisateur");
            }

            $stmt->bind_param("ss", $stuId, $stuPassword);
            if (!$stmt->execute()) {
                error_log("ERREUR insertion users: " . $stmt->error);
                throw new Exception("Erreur lors de l'insertion dans la table users");
            }
            error_log("Insertion dans users réussie");

            // Insertion dans la table students
            $sql = "INSERT INTO students (id, name, password, phone, email, sex, dob, addmissiondate, address, parentid, classid, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $link->prepare($sql);
            if (!$stmt) {
                error_log("ERREUR préparation requête students: " . $link->error);
                throw new Exception("Erreur lors de la préparation de l'insertion étudiant");
            }

            $stmt->bind_param("ssssssssssss", 
                $stuId, $stuName, $stuPassword, $stuPhone, $stuEmail, 
                $stugender, $stuDOB, $stuAddmissionDate, $stuAddress, 
                $stuParentId, $stuClassId, $admin_id
            );

            if (!$stmt->execute()) {
                error_log("ERREUR insertion students: " . $stmt->error);
                throw new Exception("Erreur lors de l'insertion dans la table students");
            }
            error_log("Insertion dans students réussie");

            // Validation de la transaction
            $link->commit();
            error_log("Transaction validée avec succès");

            echo "<div class='alert alert-success'>Étudiant enregistré avec succès!</div>";
            echo "<script>
                    setTimeout(function() {
                        window.location.href = 'manageStudent.php';
                    }, 2000);
                  </script>";

        } catch (Exception $e) {
            error_log("ERREUR pendant la transaction: " . $e->getMessage());
            $link->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        error_log("ERREUR finale: " . $e->getMessage());
        echo "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>
