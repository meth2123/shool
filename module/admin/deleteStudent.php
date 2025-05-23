<?php
include_once('main.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];

// Traitement de la suppression
if(!empty($_POST['submit'])) {
    $students = $_POST['deleteStudent'] ?? [];
    $success_count = 0;
    $error_count = 0;
    
    foreach($students as $student_id) {
        // Vérifier si l'admin a le droit de supprimer cet étudiant
        if(canAdminModifyData($link, 'students', $admin_id, $student_id)) {
            try {
                // Supprimer l'étudiant
                $sql = "DELETE FROM students WHERE id = ? AND created_by = ?";
                $stmt = $link->prepare($sql);
                $stmt->bind_param("ss", $student_id, $admin_id);
                $stmt->execute();
                
                // Supprimer l'utilisateur associé
                $sql = "DELETE FROM users WHERE userid = ?";
                $stmt = $link->prepare($sql);
                $stmt->bind_param("s", $student_id);
                $stmt->execute();
                
                // Supprimer la photo si elle existe
                $photo_path = "../images/" . $student_id . ".jpg";
                if(file_exists($photo_path)) {
                    unlink($photo_path);
                }
                
                $success_count++;
            } catch (Exception $e) {
                error_log("Erreur lors de la suppression de l'étudiant $student_id: " . $e->getMessage());
                $error_count++;
            }
        } else {
            $error_count++;
        }
    }
    
    $message = "";
    if ($success_count > 0) {
        $message .= "$success_count étudiant(s) supprimé(s) avec succès. ";
    }
    if ($error_count > 0) {
        $message .= "$error_count erreur(s) lors de la suppression.";
    }
    
    header("Location: deleteStudent.php?message=" . urlencode($message));
    exit;
}

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold mb-6">Supprimer des étudiants</h2>
        
        <!-- Message de statut -->
        <div id="statusMessage" class="mb-4 hidden"></div>
        
        <!-- Barre de recherche -->
        <div class="mb-8">
            <label for="searchId" class="block text-sm font-medium text-gray-700 mb-2">Rechercher un étudiant</label>
            <div class="flex gap-4">
                <input type="text" id="searchId" name="searchId" 
                       placeholder="Rechercher par ID, nom, téléphone ou email..." 
                       onkeyup="getStudentForDelete(this.value);"
                       class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <form action="deleteStudent.php" method="post" id="deleteForm">
            <!-- Tableau des résultats -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" id="selectAll" onclick="toggleAllCheckboxes()">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Genre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date de naissance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date d\'admission</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Adresse</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Parent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Classe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                        </tr>
                    </thead>
                    <tbody id="deleteStudentData" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="12" class="px-6 py-4 text-center text-gray-500">
                                Commencez à taper pour rechercher un étudiant...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button type="submit" name="submit" value="1" onclick="return confirmDelete();"
                        class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                    Supprimer les étudiants sélectionnés
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Inclusion des scripts JavaScript -->
<script src="JS/deleteStudent.js"></script>
<script>
function showMessage(message, type = "success") {
    const statusMessage = document.getElementById("statusMessage");
    statusMessage.className = `mb-4 p-4 rounded ${type === "success" ? "bg-green-100 text-green-700" : "bg-red-100 text-red-700"}`;
    statusMessage.textContent = message;
    statusMessage.classList.remove("hidden");
    
    setTimeout(() => {
        statusMessage.classList.add("hidden");
    }, 5000);
}

function toggleAllCheckboxes() {
    const checkboxes = document.querySelectorAll(\'input[name="deleteStudent[]"]\');
    const selectAll = document.getElementById("selectAll");
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function confirmDelete() {
    const checkboxes = document.querySelectorAll(\'input[name="deleteStudent[]"]:checked\');
    if (checkboxes.length === 0) {
        showMessage("Veuillez sélectionner au moins un étudiant à supprimer", "error");
        return false;
    }
    return confirm(`Êtes-vous sûr de vouloir supprimer ${checkboxes.length} étudiant(s) ? Cette action est irréversible.`);
}

// Afficher le message de statut si présent dans l\'URL
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has("message")) {
    showMessage(decodeURIComponent(urlParams.get("message")));
}
</script>';

include('templates/layout.php');
?>
