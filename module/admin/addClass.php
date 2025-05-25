<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$check = $_SESSION['login_id'];
$admin_name = $loged_user_name;

// Message de statut
$status_message = '';

// Traitement du formulaire
if(isset($_POST['submit'])){
    try {
        // Récupération et validation des données
        $className = trim($_POST['className']);
        $section = trim($_POST['section']);
        $room = trim($_POST['room']);
        $admin_id = $_SESSION['login_id'];

        // Validation des données
        if(empty($className)) throw new Exception("Le nom de la classe est requis");
        if(empty($section)) throw new Exception("La section est requise");
        if(empty($room)) throw new Exception("La salle est requise");

        // Générer un ID unique pour la classe
        $classId = 'CLS-' . strtoupper(substr($className, 0, 3)) . '-' . $section . '-' . rand(100, 999);

        // Vérifier si la classe existe déjà
        $check_sql = "SELECT id FROM class WHERE id = ? OR (name = ? AND section = ? AND room = ?)";
        $check_stmt = $link->prepare($check_sql);
        $check_stmt->bind_param("ssss", $classId, $className, $section, $room);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if($check_result->num_rows > 0) {
            throw new Exception("Une classe avec ces informations existe déjà");
        }

        // Insertion de la nouvelle classe
        $sql = "INSERT INTO class (id, name, section, room, created_by) VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $link->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erreur de préparation de la requête : " . $link->error);
        }

        $stmt->bind_param("sssss", $classId, $className, $section, $room, $admin_id);

        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de l'insertion : " . $stmt->error);
        }

        // Logger l'action de l'admin
        $details = json_encode([
            'class_id' => $classId,
            'class_name' => $className,
            'section' => $section,
            'room' => $room,
            'admin_name' => $admin_name
        ]);
        
        $log_sql = "INSERT INTO admin_actions (admin_id, action_type, record_id, details, created_at) 
                    VALUES (?, 'CREATE', ?, ?, NOW())";
        $log_stmt = $link->prepare($log_sql);
        $log_stmt->bind_param("sss", $admin_id, $classId, $details);
        $log_stmt->execute();

        // Redirection avec message de succès
        header("Location: manageClass.php?success=" . urlencode("Classe créée avec succès"));
        exit;

    } catch (Exception $e) {
        $status_message = '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Construction du contenu HTML par parties pour éviter les problèmes d'encodage
$content = '<div class="container py-4">';
$content .= '<div class="row justify-content-center">';
$content .= '<div class="col-md-8">';

// Message de statut
$content .= str_replace(
    ["bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4"],
    ["alert alert-danger"],
    $status_message
);

// Début de la carte
$content .= '<div class="card shadow-sm">';
$content .= '<div class="card-header bg-white">';
$content .= '<h4 class="card-title text-center mb-0">Creation d\'une Nouvelle Classe</h4>';
$content .= '</div>';

$content .= '<div class="card-body">';
$content .= '<form action="addClass.php" method="post" onsubmit="return validateClassForm();">';
$content .= '<input type="hidden" name="created_by" value="' . htmlspecialchars($check) . '">';

// Nom et Section
$content .= '<div class="row mb-3">';
$content .= '<div class="col-md-6 mb-3 mb-md-0">';
$content .= '<label for="className" class="form-label">Nom de la Classe*</label>';
$content .= '<input id="className" type="text" name="className" placeholder="Ex: 6ème" required value="' . htmlspecialchars($_POST['className'] ?? '') . '" class="form-control">';
$content .= '<div id="classNameError" class="invalid-feedback d-none"></div>';
$content .= '</div>';
$content .= '<div class="col-md-6">';
$content .= '<label for="section" class="form-label">Section*</label>';
$content .= '<input id="section" type="text" name="section" placeholder="Ex: A" required value="' . htmlspecialchars($_POST['section'] ?? '') . '" class="form-control">';
$content .= '<div id="sectionError" class="invalid-feedback d-none"></div>';
$content .= '</div>';
$content .= '</div>';

// Salle
$content .= '<div class="mb-4">';
$content .= '<label for="room" class="form-label">Salle*</label>';
$content .= '<input id="room" type="text" name="room" placeholder="Ex: 101" required value="' . htmlspecialchars($_POST['room'] ?? '') . '" class="form-control">';
$content .= '<div id="roomError" class="invalid-feedback d-none"></div>';
$content .= '</div>';

// Submit Button
$content .= '<div class="d-flex justify-content-between align-items-center">';
$content .= '<a href="manageClass.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Retour</a>';
$content .= '<button type="submit" name="submit" value="1" class="btn btn-primary"><i class="fas fa-plus-circle me-2"></i>Créer la classe</button>';
$content .= '</div>';
$content .= '</form>';
$content .= '</div>';
$content .= '</div>';
$content .= '</div>';
$content .= '</div>';
$content .= '</div>';

// Ajout du script JavaScript
$content .= '<script>
function validateClassForm() {
    let isValid = true;
    const errors = {};
    
    // Réinitialiser tous les messages d\'erreur
    document.querySelectorAll(\'.invalid-feedback\').forEach(el => {
        el.classList.add(\'d-none\');
        el.textContent = \'\';
    });
    
    document.querySelectorAll(\'.form-control\').forEach(el => {
        el.classList.remove(\'is-invalid\');
    });

    // Validate Class Name
    const className = document.getElementById("className").value.trim();
    if (!className || className.length < 2) {
        showError("className", "Le nom de la classe doit contenir au moins 2 caractères");
        isValid = false;
    }

    // Validate Section
    const section = document.getElementById("section").value.trim();
    if (!section) {
        showError("section", "La section est requise");
        isValid = false;
    }

    // Validate Room
    const room = document.getElementById("room").value.trim();
    if (!room) {
        showError("room", "La salle est requise");
        isValid = false;
    }
    
    return isValid;
}

// Fonction pour afficher les erreurs
function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorElement = document.getElementById(fieldId + "Error");
    
    if (field && errorElement) {
        field.classList.add(\'is-invalid\');
        errorElement.textContent = message;
        errorElement.classList.remove(\'d-none\');
    }
}
</script>';


include('templates/layout.php');
?> 