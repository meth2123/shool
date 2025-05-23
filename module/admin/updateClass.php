<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$check = $_SESSION['login_id'];
$admin_name = $loged_user_name;
$class_id = $_GET['id'] ?? '';

// Message de statut
$status_message = '';
$class_data = null;

// Récupérer les données de la classe
if ($class_id) {
    $sql = "SELECT * FROM class WHERE id = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("s", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $class_data = $result->fetch_assoc();

    if (!$class_data) {
        header("Location: manageClass.php?error=" . urlencode("Classe non trouvée"));
        exit;
    }
}

// Traitement du formulaire
if(isset($_POST['submit'])){
    try {
        // Récupération et validation des données
        $className = trim($_POST['className']);
        $section = trim($_POST['section']);
        $room = trim($_POST['room']);

        // Validation des données
        if(empty($className)) throw new Exception("Le nom de la classe est requis");
        if(empty($section)) throw new Exception("La section est requise");
        if(empty($room)) throw new Exception("La salle est requise");

        // Vérifier si la classe existe déjà (sauf elle-même)
        $check_sql = "SELECT id FROM class WHERE id != ? AND name = ? AND section = ? AND room = ?";
        $check_stmt = $link->prepare($check_sql);
        $check_stmt->bind_param("ssss", $class_id, $className, $section, $room);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if($check_result->num_rows > 0) {
            throw new Exception("Une autre classe avec ces informations existe déjà");
        }

        // Mise à jour de la classe
        $sql = "UPDATE class SET name = ?, section = ?, room = ? WHERE id = ?";
        $stmt = $link->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erreur de préparation de la requête : " . $link->error);
        }

        $stmt->bind_param("ssss", $className, $section, $room, $class_id);

        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la mise à jour : " . $stmt->error);
        }

        // Redirection avec message de succès
        header("Location: manageClass.php?success=" . urlencode("Classe modifiée avec succès"));
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
            <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Modification d\'une Classe</h2>
            
            <form action="updateClass.php?id=' . htmlspecialchars($class_id) . '" method="post" onsubmit="return validateClassForm();" class="space-y-6">
                <!-- Nom et Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="className" class="block text-sm font-medium text-gray-700">Nom de la Classe*</label>
                        <input id="className" type="text" name="className" placeholder="Ex: 6ème" required
                               value="' . htmlspecialchars($class_data['name'] ?? '') . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="classNameError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label for="section" class="block text-sm font-medium text-gray-700">Section*</label>
                        <input id="section" type="text" name="section" placeholder="Ex: A" required
                               value="' . htmlspecialchars($class_data['section'] ?? '') . '"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p id="sectionError" class="mt-1 text-sm text-red-600 hidden"></p>
                    </div>
                </div>

                <!-- Salle -->
                <div>
                    <label for="room" class="block text-sm font-medium text-gray-700">Salle*</label>
                    <input id="room" type="text" name="room" placeholder="Ex: 101" required
                           value="' . htmlspecialchars($class_data['room'] ?? '') . '"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <p id="roomError" class="mt-1 text-sm text-red-600 hidden"></p>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-between">
                    <a href="manageClass.php" 
                       class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        Annuler
                    </a>
                    <button type="submit" name="submit" value="1"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Modifier la classe
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function validateClassForm() {
    let isValid = true;
    const errors = {};

    // Validate Class Name
    const className = document.getElementById("className").value.trim();
    if (!className || className.length < 2) {
        errors.className = "Le nom de la classe doit contenir au moins 2 caractères";
        isValid = false;
    }

    // Validate Section
    const section = document.getElementById("section").value.trim();
    if (!section) {
        errors.section = "La section est requise";
        isValid = false;
    }

    // Validate Room
    const room = document.getElementById("room").value.trim();
    if (!room) {
        errors.room = "La salle est requise";
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