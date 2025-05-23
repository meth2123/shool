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
<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Inscription d'un Nouvel Étudiant</h2>
            
            <form action="#" method="post" onsubmit="return validateStudentForm();" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="created_by" value="{$check}">
                
                <!-- ID et Nom -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="stuId" class="block text-sm font-medium text-gray-700">ID Étudiant*</label>
                        <input id="stuId" type="text" name="studentId" placeholder="Entrer l'ID" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="stuIdError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label for="stuName" class="block text-sm font-medium text-gray-700">Nom Complet*</label>
                        <input id="stuName" type="text" name="studentName" placeholder="Entrer le nom" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="stuNameError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                </div>

                <!-- Mot de passe et Téléphone -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="stuPassword" class="block text-sm font-medium text-gray-700">Mot de passe*</label>
                        <input id="stuPassword" type="password" name="studentPassword" placeholder="Entrer le mot de passe" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="stuPasswordError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label for="stuPhone" class="block text-sm font-medium text-gray-700">Téléphone*</label>
                        <input id="stuPhone" type="tel" name="studentPhone" placeholder="Entrer le numéro" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="stuPhoneError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                </div>

                <!-- Email et Genre -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="stuEmail" class="block text-sm font-medium text-gray-700">Email*</label>
                        <input id="stuEmail" type="email" name="studentEmail" placeholder="Entrer l'email" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="stuEmailError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Genre*</label>
                        <div class="mt-2 space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="gender" value="Male" required class="form-radio text-blue-600">
                                <span class="ml-2">Masculin</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="gender" value="Female" class="form-radio text-blue-600">
                                <span class="ml-2">Féminin</span>
                            </label>
                        </div>
                        <p id="genderError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                </div>

                <!-- Date de naissance et Date d'admission -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="stuDOB" class="block text-sm font-medium text-gray-700">Date de naissance*</label>
                        <input id="stuDOB" type="date" name="studentDOB" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="stuDOBError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label for="stuAddmissionDate" class="block text-sm font-medium text-gray-700">Date d'admission</label>
                        <input id="stuAddmissionDate" type="date" name="studentAddmissionDate" value="<?php echo date('Y-m-d'); ?>" readonly 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-50">
                    </div>
                </div>

                <!-- Adresse -->
                <div>
                    <label for="stuAddress" class="block text-sm font-medium text-gray-700">Adresse*</label>
                    <textarea id="stuAddress" name="studentAddress" rows="3" placeholder="Entrer l'adresse" required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    <p id="stuAddressError" class="mt-1 text-sm text-red-600 hidden"></p>
                </div>

                <!-- ID Parent et Classe -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="stuParentId" class="block text-sm font-medium text-gray-700">ID Parent*</label>
                        <input id="stuParentId" type="text" name="studentParentId" placeholder="Entrer l'ID du parent" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="stuParentIdError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label for="stuClassId" class="block text-sm font-medium text-gray-700">Classe*</label>
                        <select id="stuClassId" name="studentClassId" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                        <p id="stuClassIdError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                </div>

                <!-- Photo -->
                <div>
                    <label for="file" class="block text-sm font-medium text-gray-700">Photo de l'étudiant</label>
                    <input id="file" type="file" name="file" accept="image/*" 
                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p id="fileError" class="mt-1 text-sm text-red-600 hidden"></p>
				</div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button type="submit" name="submit" value="1" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Inscrire l'étudiant
                    </button>
						</div>
            </form>
        </div>

        <!-- Message de succès ou d'erreur -->
        <div id="message" class="mt-4"></div>
    </div>
</div>

<script>
function validateStudentForm() {
    let isValid = true;
    const errors = {};

    // Validate Student ID
    const stuId = document.getElementById('stuId').value;
    if (!stuId || !/^[A-Za-z0-9]+$/.test(stuId)) {
        errors.stuId = "L'ID étudiant est requis et ne doit contenir que des lettres et des chiffres";
        isValid = false;
    }

    // Validate Name
    const stuName = document.getElementById('stuName').value;
    if (!stuName || stuName.length < 2) {
        errors.stuName = "Le nom doit contenir au moins 2 caractères";
        isValid = false;
    }

    // Validate Password
    const stuPassword = document.getElementById('stuPassword').value;
    if (!stuPassword || stuPassword.length < 6) {
        errors.stuPassword = "Le mot de passe doit contenir au moins 6 caractères";
        isValid = false;
    }

    // Validate Phone
    const stuPhone = document.getElementById('stuPhone').value;
    if (!stuPhone) {
        errors.stuPhone = "Le numéro de téléphone est requis";
        isValid = false;
    }

    // Validate Email
    const stuEmail = document.getElementById('stuEmail').value;
    if (!stuEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(stuEmail)) {
        errors.stuEmail = "Veuillez entrer une adresse email valide";
        isValid = false;
    }

    // Validate Gender
    const gender = document.querySelector('input[name="gender"]:checked');
    if (!gender) {
        errors.gender = "Veuillez sélectionner un genre";
        isValid = false;
    }

    // Validate DOB
    const stuDOB = document.getElementById('stuDOB').value;
    if (!stuDOB) {
        errors.stuDOB = "La date de naissance est requise";
        isValid = false;
    }

    // Validate Address
    const stuAddress = document.getElementById('stuAddress').value;
    if (!stuAddress || stuAddress.length < 5) {
        errors.stuAddress = "L'adresse doit contenir au moins 5 caractères";
        isValid = false;
    }

    // Validate Parent ID
    const stuParentId = document.getElementById('stuParentId').value;
    if (!stuParentId) {
        errors.stuParentId = "L'ID du parent est requis";
        isValid = false;
    }

    // Validate Class
    const stuClassId = document.getElementById('stuClassId').value;
    if (!stuClassId) {
        errors.stuClassId = "Veuillez sélectionner une classe";
        isValid = false;
    }

    // Display errors if any
    Object.keys(errors).forEach(field => {
        const errorElement = document.getElementById(field + 'Error');
        if (errorElement) {
            errorElement.textContent = errors[field];
            errorElement.classList.remove('hidden');
        }
    });

    return isValid;
}
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
