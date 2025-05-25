<?php
include_once('main.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];

// Récupérer uniquement les étudiants créés par l'admin connecté
$sql = "SELECT * FROM students WHERE created_by = ?";
$stmt = $link->prepare($sql);

// Débogage
error_log("Admin ID: " . $admin_id);
error_log("Requête SQL: " . $sql);

if (!$stmt) {
    error_log("Erreur de préparation: " . $link->error);
    die("Erreur de préparation: " . $link->error);
}

$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    error_log("Erreur d'exécution: " . $stmt->error);
    die("Erreur d'exécution: " . $stmt->error);
}

error_log("Nombre de résultats: " . $result->num_rows);

$string = "";
$images_dir = "../images/";

$content = '
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="card-title mb-0">Liste des étudiants</h4>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
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
                            <tbody>';

while($row = $result->fetch_assoc()) {
    error_log("Données étudiant: " . print_r($row, true));
    $picname = $row['id'];
    $content .= '
        <tr>
            <td>'.$row['id'].'</td>
            <td>'.$row['name'].'</td>
            <td>'.$row['phone'].'</td>
            <td>'.$row['email'].'</td>
            <td><span class="badge bg-'.($row['sex'] == 'Male' ? 'primary' : 'info').'">'.$row['sex'].'</span></td>
            <td>'.$row['dob'].'</td>
            <td>'.$row['addmissiondate'].'</td>
            <td class="text-truncate" style="max-width: 150px;">'.$row['address'].'</td>
            <td>'.$row['parentid'].'</td>
            <td>'.$row['classid'].'</td>
            <td>
                <img src="'.$images_dir.$picname.'.jpg" alt="'.$picname.'" class="rounded-circle" width="50" height="50">
            </td>
        </tr>';
}

$content .= '
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                    <span class="text-muted">Total : '.mysqli_num_rows($result).' étudiant(s)</span>
                    <a href="manageStudent.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>';

include('templates/layout.php');
?>
