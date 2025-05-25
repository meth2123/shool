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
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="card-title mb-0">Supprimer des étudiants</h4>
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
                                   placeholder="Rechercher par ID, nom, téléphone ou email..." 
                                   onkeyup="getStudentForDelete(this.value);">
                        </div>
                    </div>

                    <form action="deleteStudent.php" method="post" id="deleteForm">
                        <!-- Tableau des résultats -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAll" onclick="toggleAllCheckboxes()">
                                                <label class="form-check-label" for="selectAll"></label>
                                            </div>
                                        </th>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Téléphone</th>
                                        <th>Email</th>
                                        <th>Genre</th>
                                        <th>Date de naissance</th>
                                        <th>Date d\'admission</th>
                                        <th>Adresse</th>
                                        <th>ID Parent</th>
                                        <th>ID Classe</th>
                                        <th>Photo</th>
                                    </tr>
                                </thead>
                                <tbody id="deleteStudentData">
                                    <tr>
                                        <td colspan="12" class="text-center text-muted">
                                            <div class="py-4">
                                                <i class="fas fa-search fa-2x mb-3"></i>
                                                <p>Commencez à taper pour rechercher un étudiant...</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="manageStudent.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Retour
                            </a>
                            <button type="submit" name="submit" value="1" onclick="return confirmDelete();" class="btn btn-danger">
                                <i class="fas fa-trash-alt me-2"></i>Supprimer les étudiants sélectionnés
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inclusion des scripts JavaScript -->
<script src="JS/deleteStudent.js"></script>
<script>
function showMessage(message, type = "success") {
    const statusMessage = document.getElementById("statusMessage");
    statusMessage.className = `alert alert-${type === "success" ? "success" : "danger"} mb-4`;
    statusMessage.textContent = message;
    statusMessage.classList.remove("d-none");
    
    setTimeout(() => {
        statusMessage.classList.add("d-none");
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
