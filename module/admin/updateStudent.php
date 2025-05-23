<?php
include_once('main.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold mb-6">Modifier un étudiant</h2>
        
        <!-- Message de statut -->
        <div id="statusMessage" class="mb-4 hidden"></div>
        
        <!-- Barre de recherche -->
        <div class="mb-8">
            <label for="searchId" class="block text-sm font-medium text-gray-700 mb-2">Rechercher un étudiant</label>
            <div class="flex gap-4">
                <input type="text" id="searchId" name="searchId" 
                       placeholder="Rechercher par ID ou nom..." 
                       onkeyup="getStudentForUpdate(this.value);"
                       class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <!-- Tableau des résultats -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mot de passe</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Genre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date de naissance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date d\'admission</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Adresse</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Parent</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Classe</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="updateStudentData" class="bg-white divide-y divide-gray-200">
                    <!-- Les résultats de recherche seront insérés ici -->
                    <tr>
                        <td colspan="13" class="px-6 py-4 text-center text-gray-500">
                            Commencez à taper pour rechercher un étudiant...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Inclusion des scripts JavaScript -->
<script src="JS/updateStudent.js"></script>
<script>
function submitForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        // Validation basique
        const requiredFields = ["name", "phone", "email", "parentid", "classid"];
        let isValid = true;
        
        requiredFields.forEach(field => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input && !input.value.trim()) {
                showMessage(`Le champ ${field} est requis`, "error");
                isValid = false;
            }
        });
        
        if (isValid) {
            form.submit();
        }
    }
}

function showMessage(message, type = "success") {
    const statusMessage = document.getElementById("statusMessage");
    statusMessage.className = `mb-4 p-4 rounded ${type === "success" ? "bg-green-100 text-green-700" : "bg-red-100 text-red-700"}`;
    statusMessage.textContent = message;
    statusMessage.classList.remove("hidden");
    
    setTimeout(() => {
        statusMessage.classList.add("hidden");
    }, 5000);
}

// Afficher le message de succès si présent dans l\'URL
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has("success")) {
    showMessage("L\'étudiant a été mis à jour avec succès!");
}
if (urlParams.has("error")) {
    showMessage(decodeURIComponent(urlParams.get("error")), "error");
}
</script>';

include('templates/layout.php');
?>
