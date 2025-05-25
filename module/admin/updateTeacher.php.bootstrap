<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$admin_id = $_SESSION['login_id'];
$teacher_id = $_GET['id'] ?? '';
$status_message = '';
$teacher_data = null;

// Récupérer les données de l'enseignant
if ($teacher_id) {
    $sql = "SELECT * FROM teachers WHERE id = ? AND created_by = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("ss", $teacher_id, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher_data = $result->fetch_assoc();

    if (!$teacher_data) {
        header("Location: manageTeacher.php?error=" . urlencode("Enseignant non trouvé ou accès non autorisé"));
        exit;
    }
}

if(isset($_POST['submit'])) {
    try {
        // Récupération et validation des données
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $gender = $_POST['gender'];
        $address = trim($_POST['address']);
        $dob = $_POST['dob'];
        $password = trim($_POST['password']);
        $hiredate = $_POST['hiredate'];
        $salary = trim($_POST['salary']);

        // Validation des données
        if(empty($name)) throw new Exception("Le nom est requis");
        if(empty($email)) throw new Exception("L'email est requis");
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Format d'email invalide");
        if(empty($phone)) throw new Exception("Le téléphone est requis");
        if(empty($dob)) throw new Exception("La date de naissance est requise");
        if(empty($hiredate)) throw new Exception("La date d'embauche est requise");
        if(!is_numeric($salary)) throw new Exception("Le salaire doit être un nombre");

        // Vérifier si l'email existe déjà (sauf pour cet enseignant)
        $check_sql = "SELECT id FROM teachers WHERE email = ? AND id != ?";
        $check_stmt = $link->prepare($check_sql);
        $check_stmt->bind_param("ss", $email, $teacher_id);
        $check_stmt->execute();
        if($check_stmt->get_result()->num_rows > 0) {
            throw new Exception("Un enseignant avec cet email existe déjà");
        }

        // Mise à jour de l'enseignant
        $link->begin_transaction();
        
        try {
            // Mise à jour du mot de passe dans la table users si nécessaire
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $user_sql = "UPDATE users SET password = ? WHERE userid = ?";
                $user_stmt = $link->prepare($user_sql);
                if (!$user_stmt) {
                    throw new Exception("Erreur de préparation de la requête users : " . $link->error);
                }
                
                $user_stmt->bind_param("ss", $hashed_password, $teacher_id);
                
                if (!$user_stmt->execute()) {
                    throw new Exception("Erreur lors de la mise à jour dans users : " . $user_stmt->error);
                }
            }

            // Mise à jour dans la table teachers
            if (!empty($password)) {
                $sql = "UPDATE teachers SET name = ?, password = ?, phone = ?, email = ?, sex = ?, dob = ?, address = ?, hiredate = ?, salary = ? WHERE id = ? AND created_by = ?";
                $stmt = $link->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Erreur de préparation de la requête teachers : " . $link->error);
                }

                $stmt->bind_param("ssssssssdss", $name, $password, $phone, $email, $gender, $dob, $address, $hiredate, $salary, $teacher_id, $admin_id);
            } else {
                $sql = "UPDATE teachers SET name = ?, phone = ?, email = ?, sex = ?, dob = ?, address = ?, hiredate = ?, salary = ? WHERE id = ? AND created_by = ?";
                $stmt = $link->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Erreur de préparation de la requête teachers : " . $link->error);
                }

                $stmt->bind_param("sssssssdss", $name, $phone, $email, $gender, $dob, $address, $hiredate, $salary, $teacher_id, $admin_id);
            }

            if (!$stmt->execute()) {
                throw new Exception("Erreur lors de la mise à jour dans teachers : " . $stmt->error);
            }

            $link->commit();
            header("Location: manageTeacher.php?success=" . urlencode("Enseignant modifié avec succès"));
            exit;

        } catch (Exception $e) {
            $link->rollback();
            $status_message = '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
        }

    } catch (Exception $e) {
        $status_message = '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

$content = '
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            ' . $status_message . '
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Modifier l\'Enseignant</h4>
                    <span class="badge bg-warning text-dark">ID: ' . htmlspecialchars($teacher_id) . '</span>
                </div>
                <div class="card-body">
                    <form action="updateTeacher.php?id=' . htmlspecialchars($teacher_id) . '" method="post" onsubmit="return validateTeacherForm();">
                        <!-- Nom et Email -->
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="name" class="form-label">Nom*</label>
                                <input type="text" id="name" name="name" required
                                       value="' . htmlspecialchars($teacher_data['name'] ?? '') . '"
                                       placeholder="Entrer le nom" class="form-control">
                                <div id="nameError" class="invalid-feedback d-none"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email*</label>
                                <input type="email" id="email" name="email" required
                                       value="' . htmlspecialchars($teacher_data['email'] ?? '') . '"
                                       placeholder="Entrer l\'email" class="form-control">
                                <div id="emailError" class="invalid-feedback d-none"></div>
                            </div>
                        </div>

                        <!-- Téléphone et Genre -->
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="phone" class="form-label">Téléphone*</label>
                                <input type="tel" id="phone" name="phone" required
                                       value="' . htmlspecialchars($teacher_data['phone'] ?? '') . '"
                                       placeholder="Entrer le numéro" class="form-control">
                                <div id="phoneError" class="invalid-feedback d-none"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Genre*</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="genderMale" value="male" ' . (($teacher_data['sex'] ?? '') === 'male' ? 'checked' : '') . '>
                                        <label class="form-check-label" for="genderMale">
                                            Masculin
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="female" ' . (($teacher_data['sex'] ?? '') === 'female' ? 'checked' : '') . '>
                                        <label class="form-check-label" for="genderFemale">
                                            Féminin
                                        </label>
                                    </div>
                                </div>
                                <div id="genderError" class="invalid-feedback d-none"></div>
                            </div>
                        </div>

                        <!-- Date de naissance et Mot de passe -->
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="dob" class="form-label">Date de naissance*</label>
                                <input type="date" id="dob" name="dob" required
                                       value="' . htmlspecialchars($teacher_data['dob'] ?? '') . '"
                                       class="form-control">
                                <div id="dobError" class="invalid-feedback d-none"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Mot de passe (laisser vide pour ne pas modifier)</label>
                                <div class="input-group">
                                    <input type="password" id="password" name="password"
                                           placeholder="Nouveau mot de passe" class="form-control">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility()">
                                        <i class="fas fa-eye" id="password-toggle-icon"></i>
                                    </button>
                                </div>
                                <div id="passwordError" class="invalid-feedback d-none"></div>
                            </div>
                        </div>

                        <!-- Date d\'embauche et Salaire -->
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="hiredate" class="form-label">Date d\'embauche*</label>
                                <input type="date" id="hiredate" name="hiredate" required
                                       value="' . htmlspecialchars($teacher_data['hiredate'] ?? '') . '"
                                       class="form-control">
                                <div id="hiredateError" class="invalid-feedback d-none"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="salary" class="form-label">Salaire*</label>
                                <div class="input-group">
                                    <input type="number" id="salary" name="salary" required step="0.01" min="0"
                                           value="' . htmlspecialchars($teacher_data['salary'] ?? '') . '"
                                           placeholder="Entrer le salaire" class="form-control">
                                    <span class="input-group-text">€</span>
                                </div>
                                <div id="salaryError" class="invalid-feedback d-none"></div>
                            </div>
                        </div>

                        <!-- Adresse -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label for="address" class="form-label">Adresse</label>
                                <textarea id="address" name="address" rows="3" placeholder="Entrer l\'adresse"
                                          class="form-control">' . htmlspecialchars($teacher_data['address'] ?? '') . '</textarea>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="row">
                            <div class="col-12 d-flex justify-content-between align-items-center">
                                <a href="manageTeacher.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Retour
                                </a>
                                <button type="submit" name="submit" value="1" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Modifier l\'enseignant
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Aide contextuelle -->
            <div class="card mt-4 bg-light">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-info-circle me-2 text-primary"></i>Informations importantes</h5>
                    <ul class="mb-0 ps-3">
                        <li>Laissez le champ mot de passe vide pour conserver l\'actuel</li>
                        <li>L\'email doit être unique et valide</li>
                        <li>Tous les champs marqués d\'un astérisque (*) sont obligatoires</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
';

// Ajouter le script JavaScript séparément, pas dans la chaîne PHP
$js_content = '
<script>
function togglePasswordVisibility() {
    var passwordInput = document.getElementById("password");
    var toggleIcon = document.getElementById("password-toggle-icon");
    
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleIcon.classList.remove("fa-eye");
        toggleIcon.classList.add("fa-eye-slash");
    } else {
        passwordInput.type = "password";
        toggleIcon.classList.remove("fa-eye-slash");
        toggleIcon.classList.add("fa-eye");
    }
}

function validateTeacherForm() {
    let isValid = true;
    
    // Réinitialiser tous les messages d\'erreur
    document.querySelectorAll(".invalid-feedback").forEach(function(el) {
        el.classList.add("d-none");
        el.textContent = "";
    });
    
    document.querySelectorAll(".form-control, .form-select, .form-check-input").forEach(el => {
        el.classList.remove("is-invalid");
    });

    // Validate Name
    const name = document.getElementById("name").value.trim();
    if (!name || name.length < 2) {
        showError("name", "Le nom doit contenir au moins 2 caractères");
        isValid = false;
    }

    // Validate Email
    const email = document.getElementById("email").value.trim();
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showError("email", "Email invalide");
        isValid = false;
    }

    // Validate Phone
    const phone = document.getElementById("phone").value.trim();
    if (!phone || phone.length < 8) {
        showError("phone", "Numéro de téléphone invalide");
        isValid = false;
    }

    // Validate Gender
    const gender = document.querySelector("input[name=\'gender\']:checked");
    if (!gender) {
        document.getElementById("genderError").textContent = "Veuillez sélectionner un genre";
        document.getElementById("genderError").classList.remove("d-none");
        document.querySelectorAll("input[name=\'gender\']").forEach(el => {
            el.classList.add("is-invalid");
        });
        isValid = false;
    }

    // Validate Password (only if not empty)
    const password = document.getElementById("password").value;
    if (password && password.length < 6) {
        showError("password", "Le mot de passe doit contenir au moins 6 caractères");
        isValid = false;
    }

    // Validate Date of Birth
    const dob = document.getElementById("dob").value;
    if (!dob) {
        showError("dob", "La date de naissance est requise");
        isValid = false;
    }

    // Validate Hire Date
    const hiredate = document.getElementById("hiredate").value;
    if (!hiredate) {
        showError("hiredate", "La date d\'embauche est requise");
        isValid = false;
    }

    // Validate Salary
    const salary = document.getElementById("salary").value;
    if (!salary || isNaN(salary) || parseFloat(salary) < 0) {
        showError("salary", "Le salaire doit être un nombre positif");
        isValid = false;
    }

    return isValid;
}

// Fonction pour afficher les erreurs
function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorElement = document.getElementById(fieldId + "Error");
    
    if (field && errorElement) {
        field.classList.add("is-invalid");
        errorElement.textContent = message;
        errorElement.classList.remove("d-none");
    }
}
</script>
';

// Inclure le contenu et le script JavaScript dans le layout
include('templates/layout.php');

// Ajouter le script JavaScript après le layout
echo $js_content;
?>
