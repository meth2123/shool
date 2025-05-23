<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

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
            throw new Exception("Un autre enseignant utilise déjà cet email");
        }

        // Construire la requête de mise à jour avec vérification du created_by
        if (!empty($password)) {
            $sql = "UPDATE teachers SET name = ?, email = ?, phone = ?, sex = ?, dob = ?, address = ?, password = ?, hiredate = ?, salary = ? WHERE id = ? AND created_by = ?";
            $stmt = $link->prepare($sql);
            $stmt->bind_param("ssssssssdss", $name, $email, $phone, $gender, $dob, $address, $password, $hiredate, $salary, $teacher_id, $admin_id);
        } else {
            $sql = "UPDATE teachers SET name = ?, email = ?, phone = ?, sex = ?, dob = ?, address = ?, hiredate = ?, salary = ? WHERE id = ? AND created_by = ?";
            $stmt = $link->prepare($sql);
            $stmt->bind_param("sssssssdss", $name, $email, $phone, $gender, $dob, $address, $hiredate, $salary, $teacher_id, $admin_id);
        }

        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la mise à jour : " . $stmt->error);
        }

        if ($stmt->affected_rows === 0) {
            throw new Exception("Modification non autorisée ou enseignant non trouvé");
        }

        header("Location: manageTeacher.php?success=" . urlencode("Enseignant modifié avec succès"));
        exit;

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
            <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Modifier l\'Enseignant</h2>
            
            <form action="updateTeacher.php?id=' . htmlspecialchars($teacher_id) . '" method="post" onsubmit="return validateTeacherForm();" class="space-y-6">
                <!-- Nom et Email -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nom*</label>
                        <input type="text" id="name" name="name" required
                               value="' . htmlspecialchars($teacher_data['name'] ?? '') . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="nameError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email*</label>
                        <input type="email" id="email" name="email" required
                               value="' . htmlspecialchars($teacher_data['email'] ?? '') . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="emailError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                </div>

                <!-- Téléphone et Genre -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Téléphone*</label>
                        <input type="tel" id="phone" name="phone" required
                               value="' . htmlspecialchars($teacher_data['phone'] ?? '') . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="phoneError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700">Genre*</label>
                        <select id="gender" name="gender" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="male" ' . (($teacher_data['sex'] ?? '') == 'male' ? 'selected' : '') . '>Homme</option>
                            <option value="female" ' . (($teacher_data['sex'] ?? '') == 'female' ? 'selected' : '') . '>Femme</option>
                        </select>
                    </div>
                </div>

                <!-- Date de naissance et Mot de passe -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="dob" class="block text-sm font-medium text-gray-700">Date de naissance*</label>
                        <input type="date" id="dob" name="dob" required
                               value="' . htmlspecialchars($teacher_data['dob'] ?? '') . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="dobError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Nouveau mot de passe</label>
                        <input type="password" id="password" name="password"
                               placeholder="Laisser vide pour ne pas changer"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="passwordError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                </div>

                <!-- Date d\'embauche et Salaire -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="hiredate" class="block text-sm font-medium text-gray-700">Date d\'embauche*</label>
                        <input type="date" id="hiredate" name="hiredate" required
                               value="' . htmlspecialchars($teacher_data['hiredate'] ?? '') . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="hiredateError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label for="salary" class="block text-sm font-medium text-gray-700">Salaire*</label>
                        <input type="number" id="salary" name="salary" required step="0.01" min="0"
                               value="' . htmlspecialchars($teacher_data['salary'] ?? '') . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="salaryError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                </div>

                <!-- Adresse -->
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">Adresse</label>
                    <textarea id="address" name="address" rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">' . 
                              htmlspecialchars($teacher_data['address'] ?? '') . '</textarea>
				</div>

                <!-- Submit Buttons -->
                <div class="flex justify-between">
                    <a href="manageTeacher.php" 
                       class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        Annuler
                    </a>
                    <button type="submit" name="submit" value="1"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Modifier l\'enseignant
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

    // Validate Password if provided
    const password = document.getElementById("password").value;
    if (password && password.length < 6) {
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
