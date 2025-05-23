<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];
$status_message = '';

if(isset($_POST['submit'])) {
    try {
        // Récupération et validation des données
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $gender = $_POST['gender'];
        $password = trim($_POST['password']);
        $address = trim($_POST['address']);
        $dob = $_POST['dob'];
        $hiredate = $_POST['hiredate'];
        $salary = trim($_POST['salary']);

        // Validation des données
        if(empty($name)) throw new Exception("Le nom est requis");
        if(empty($email)) throw new Exception("L'email est requis");
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Format d'email invalide");
        if(empty($phone)) throw new Exception("Le téléphone est requis");
        if(empty($password)) throw new Exception("Le mot de passe est requis");
        if(empty($dob)) throw new Exception("La date de naissance est requise");
        if(empty($hiredate)) throw new Exception("La date d'embauche est requise");
        if(!is_numeric($salary)) throw new Exception("Le salaire doit être un nombre");

        // Générer un ID unique pour l'enseignant
        $teacher_id = 'TE-' . strtoupper(substr($name, 0, 3)) . '-' . rand(1000, 9999);

        // Vérifier si l'email existe déjà
        $check_sql = "SELECT id FROM teachers WHERE email = ?";
        $check_stmt = $link->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        if($check_stmt->get_result()->num_rows > 0) {
            throw new Exception("Un enseignant avec cet email existe déjà");
        }

        // Insertion du nouvel enseignant
        $link->begin_transaction();
        
        try {
            // Insertion dans la table users
            $user_sql = "INSERT INTO users (userid, password, usertype) VALUES (?, ?, 'teacher')";
            $user_stmt = $link->prepare($user_sql);
            if (!$user_stmt) {
                throw new Exception("Erreur de préparation de la requête users : " . $link->error);
            }
            
            // On hash le mot de passe pour la table users
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_stmt->bind_param("ss", $teacher_id, $hashed_password);
            
            if (!$user_stmt->execute()) {
                throw new Exception("Erreur lors de l'insertion dans users : " . $user_stmt->error);
            }

            // Insertion dans la table teachers
            $sql = "INSERT INTO teachers (id, name, password, phone, email, sex, dob, address, hiredate, salary, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $link->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erreur de préparation de la requête teachers : " . $link->error);
            }

            $stmt->bind_param("sssssssssds", $teacher_id, $name, $password, $phone, $email, $gender, $dob, $address, $hiredate, $salary, $admin_id);

            if (!$stmt->execute()) {
                throw new Exception("Erreur lors de l'insertion dans teachers : " . $stmt->error);
            }

            $link->commit();
            header("Location: manageTeacher.php?success=" . urlencode("Enseignant ajouté avec succès"));
            exit;

        } catch (Exception $e) {
            $link->rollback();
            $status_message = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">' . 
                            htmlspecialchars($e->getMessage()) . '</div>';
        }

    } catch (Exception $e) {
        $status_message = '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">' . 
                         htmlspecialchars($e->getMessage()) . '</div>';
    }
}

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        ' . $status_message . '
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Ajouter un Enseignant</h2>
            
            <form action="addTeacher.php" method="post" onsubmit="return validateTeacherForm();" class="space-y-6">
                <!-- Nom et Email -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nom*</label>
                        <input type="text" id="name" name="name" required
                               value="' . htmlspecialchars($_POST['name'] ?? '') . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="nameError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email*</label>
                        <input type="email" id="email" name="email" required
                               value="' . htmlspecialchars($_POST['email'] ?? '') . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="emailError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                </div>

                <!-- Téléphone et Genre -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Téléphone*</label>
                        <input type="tel" id="phone" name="phone" required
                               value="' . htmlspecialchars($_POST['phone'] ?? '') . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="phoneError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700">Genre*</label>
                        <select id="gender" name="gender" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="male" ' . (isset($_POST['gender']) && $_POST['gender'] == 'male' ? 'selected' : '') . '>Homme</option>
                            <option value="female" ' . (isset($_POST['gender']) && $_POST['gender'] == 'female' ? 'selected' : '') . '>Femme</option>
                        </select>
                    </div>
                </div>

                <!-- Date de naissance et Mot de passe -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="dob" class="block text-sm font-medium text-gray-700">Date de naissance*</label>
                        <input type="date" id="dob" name="dob" required
                               value="' . htmlspecialchars($_POST['dob'] ?? '') . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="dobError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Mot de passe*</label>
                        <input type="password" id="password" name="password" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="passwordError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                </div>

                <!-- Date d\'embauche et Salaire -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="hiredate" class="block text-sm font-medium text-gray-700">Date d\'embauche*</label>
                        <input type="date" id="hiredate" name="hiredate" required
                               value="' . htmlspecialchars($_POST['hiredate'] ?? date('Y-m-d')) . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="hiredateError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label for="salary" class="block text-sm font-medium text-gray-700">Salaire*</label>
                        <input type="number" id="salary" name="salary" required step="0.01" min="0"
                               value="' . htmlspecialchars($_POST['salary'] ?? '') . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="salaryError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                </div>

                <!-- Adresse -->
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">Adresse</label>
                    <textarea id="address" name="address" rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">' . 
                              htmlspecialchars($_POST['address'] ?? '') . '</textarea>
				</div>

                <!-- Submit Buttons -->
                <div class="flex justify-between">
                    <a href="manageTeacher.php" 
                       class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        Annuler
                    </a>
                    <button type="submit" name="submit" value="1"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Ajouter l\'enseignant
                    </button>
						</div>
            </form>
        </div>
    </div>
</div>

<script>
function validateTeacherForm() {
    let isValid = true;
    const errors = {};

    // Validate Name
    const name = document.getElementById("name").value.trim();
    if (!name || name.length < 2) {
        errors.name = "Le nom doit contenir au moins 2 caractères";
        isValid = false;
    }

    // Validate Email
    const email = document.getElementById("email").value.trim();
    if (!email || !email.includes("@")) {
        errors.email = "Email invalide";
        isValid = false;
    }

    // Validate Phone
    const phone = document.getElementById("phone").value.trim();
    if (!phone || phone.length < 8) {
        errors.phone = "Numéro de téléphone invalide";
        isValid = false;
    }

    // Validate Password
    const password = document.getElementById("password").value;
    if (!password || password.length < 6) {
        errors.password = "Le mot de passe doit contenir au moins 6 caractères";
        isValid = false;
    }

    // Validate Date of Birth
    const dob = document.getElementById("dob").value;
    if (!dob) {
        errors.dob = "La date de naissance est requise";
        isValid = false;
    }

    // Validate Hire Date
    const hiredate = document.getElementById("hiredate").value;
    if (!hiredate) {
        errors.hiredate = "La date d\'embauche est requise";
        isValid = false;
    }

    // Validate Salary
    const salary = document.getElementById("salary").value;
    if (!salary || isNaN(salary) || parseFloat(salary) < 0) {
        errors.salary = "Le salaire doit être un nombre positif";
        isValid = false;
    }

    // Clear previous errors
    document.querySelectorAll(".text-red-600").forEach(el => {
        el.textContent = "";
        el.classList.add("hidden");
    });

    // Display new errors if any
    Object.keys(errors).forEach(field => {
        const errorElement = document.getElementById(field + "Error");
        if (errorElement) {
            errorElement.textContent = errors[field];
            errorElement.classList.remove("hidden");
        }
    });

    return isValid;
}
</script>';

include('templates/layout.php');
?>
