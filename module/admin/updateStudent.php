<?php
include_once('main.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];

$content = '
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="card-title mb-0">Modifier un étudiant</h4>
                </div>
                <div class="card-body">
                    <!-- Message de statut -->
                    <div id="statusMessage" class="alert d-none mb-4"></div>
                    
                    <!-- Barre de recherche -->
                    <div class="mb-4">
                        <label for="searchId" class="form-label">Rechercher un étudiant</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="searchId" name="searchId" 
                                   class="form-control" 
                                   placeholder="Rechercher par ID ou nom..." 
                                   onkeyup="getStudentForUpdate(this.value);">
                        </div>
                    </div>

                    <!-- Tableau des résultats -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Mot de passe</th>
                                    <th>Téléphone</th>
                                    <th>Email</th>
                                    <th>Genre</th>
                                    <th>Date de naissance</th>
                                    <th>Date d\'admission</th>
                                    <th>Adresse</th>
                                    <th>ID Parent</th>
                                    <th>ID Classe</th>
                                    <th>Photo</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="updateStudentData">
                                <!-- Les résultats de recherche seront insérés ici -->
                                <tr>
                                    <td colspan="13" class="text-center text-muted">
                                        <div class="py-4">
                                            <i class="fas fa-search fa-2x mb-3"></i>
                                            <p>Commencez à taper pour rechercher un étudiant...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <a href="manageStudent.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour à la gestion des étudiants
                    </a>
                </div>
            </div>
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
    statusMessage.className = `alert alert-${type === "success" ? "success" : "danger"} mb-4`;
    statusMessage.textContent = message;
    statusMessage.classList.remove("d-none");
    
    setTimeout(() => {
        statusMessage.classList.add("d-none");
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
