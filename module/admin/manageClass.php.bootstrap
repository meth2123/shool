<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$admin_id = $_SESSION['login_id'];

// Récupérer les classes de l'admin connecté
$sql = "SELECT *, 
        CASE 
            WHEN created_by = ? THEN 'Ma classe'
            ELSE 'Classe par défaut'
        END as class_type 
        FROM class 
        WHERE created_by = ? OR created_by = '21'
        ORDER BY name, section";
$stmt = $link->prepare($sql);
$stmt->bind_param("ss", $admin_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();

// Message de succès/erreur
$message = '';
if (isset($_GET['success'])) {
    $message = '<div class="alert alert-success mb-4">' . htmlspecialchars($_GET['success']) . '</div>';
} elseif (isset($_GET['error'])) {
    $message = '<div class="alert alert-danger mb-4">' . htmlspecialchars($_GET['error']) . '</div>';
}

$content = '
<div class="container py-4">
    ' . $message . '
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <h2 class="h3 mb-0">Gestion des Classes</h2>
        <a href="addClass.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>Ajouter une classe
        </a>
    </div>

    <!-- Affichage pour écrans larges -->
    <div class="card shadow-sm d-none d-md-block mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Type</th>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Section</th>
                            <th>Salle</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $isDefaultClass = $row['created_by'] === '21';
        $rowClass = $isDefaultClass ? 'table-light' : '';
        $badgeClass = $isDefaultClass ? 'bg-secondary' : 'bg-success';
        
        $content .= '
                    <tr class="' . $rowClass . '">
                        <td>
                            <span class="badge ' . $badgeClass . '">' . htmlspecialchars($row['class_type']) . '</span>
                        </td>
                        <td>' . htmlspecialchars($row['id']) . '</td>
                        <td>' . htmlspecialchars($row['name']) . '</td>
                        <td>' . htmlspecialchars($row['section']) . '</td>
                        <td>' . htmlspecialchars($row['room']) . '</td>
                        <td>
                            <a href="updateClass.php?id=' . htmlspecialchars($row['id']) . '" class="btn btn-sm btn-outline-primary me-2">
                                <i class="fas fa-edit me-1"></i>Modifier
                            </a>
                            <a href="deleteClass.php?id=' . htmlspecialchars($row['id']) . '" class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer cette classe ?\');">
                                <i class="fas fa-trash-alt me-1"></i>Supprimer
                            </a>
                        </td>
                    </tr>';
    }
} else {
    $content .= '
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            Aucune classe trouvée. <a href="addClass.php" class="text-primary">Créer une nouvelle classe</a>
                        </td>
                    </tr>';
}

$content .= '
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Vue pour mobiles (cartes) -->
    <div class="d-md-none">';
    
    // Réinitialiser le pointeur de résultat pour parcourir à nouveau les données
    $result->data_seek(0);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $isDefaultClass = $row["created_by"] === "21";
            $borderClass = $isDefaultClass ? "border-secondary" : "border-success";
            $badgeClass = $isDefaultClass ? "bg-secondary" : "bg-success";
            
            $content .= '
            <div class="card shadow-sm mb-3 border-start ' . $borderClass . ' border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title mb-0">' . htmlspecialchars($row["name"]) . ' ' . htmlspecialchars($row["section"]) . '</h5>
                        <span class="badge ' . $badgeClass . '">
                            ' . htmlspecialchars($row["class_type"]) . '
                        </span>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <span class="text-muted small">ID:</span>
                            <p class="mb-0 fw-medium">' . htmlspecialchars($row["id"]) . '</p>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small">Salle:</span>
                            <p class="mb-0 fw-medium">' . htmlspecialchars($row["room"]) . '</p>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <a href="updateClass.php?id=' . htmlspecialchars($row["id"]) . '" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit me-1"></i>Modifier
                        </a>
                        <a href="deleteClass.php?id=' . htmlspecialchars($row["id"]) . '" class="btn btn-sm btn-outline-danger"
                           onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer cette classe ?\');">
                            <i class="fas fa-trash-alt me-1"></i>Supprimer
                        </a>
                    </div>
                </div>
            </div>';
        }
    } else {
        $content .= '
            <div class="card shadow-sm">
                <div class="card-body text-center text-muted">
                    Aucune classe trouvée. <a href="addClass.php" class="text-primary">Créer une nouvelle classe</a>
                </div>
            </div>';
    }
    
    $content .= '
    </div>
</div>';

include('templates/layout.php');
?>
