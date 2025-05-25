<?php
include_once('main.php');
require_once('../../db/config.php');
include_once('../../service/db_utils.php');

// Ensure user is logged in and has admin privileges
if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$check = $_SESSION['login_id'];

// Initialize database connection
$conn = getDbConnection();

// Verify admin privileges using prepared statement
$sql = "SELECT id FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    header("Location: ../../index.php");
    exit();
}

$stmt->bind_param("s", $check);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../../index.php");
    exit();
}

// Get the admin's ID for created_by filtering
$admin_id = $check;
// Store admin_id in session for AJAX requests
$_SESSION['admin_id'] = $admin_id;

// Initialize database connection for the rest of the page
$conn = getDbConnection();

// Traiter la justification d'une absence
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['justify'])) {
    $student_id = $_POST['student_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $course_time = $_POST['course_time'] ?? '';
    
    if ($student_id && $date && $course_time) {
        // Vérifier si une entrée existe déjà
        $check_query = "SELECT id FROM attendance WHERE CAST(attendedid AS CHAR) = CAST(? AS CHAR) AND DATE(date) = ? AND TIME(date) = ?";
        $existing = db_fetch_row($check_query, [$student_id, $date, $course_time], 'sss');
        
        if ($existing) {
            // Mettre à jour l'entrée existante
            $update_query = "UPDATE attendance SET date = CONCAT(?, ' ', ?) WHERE CAST(id AS CHAR) = CAST(? AS CHAR)";
            db_execute($update_query, [$date, $course_time, $existing['id']], 'sss');
        } else {
            // Créer une nouvelle entrée
            $insert_query = "INSERT INTO attendance (date, attendedid) VALUES (CONCAT(?, ' ', ?), ?)";
            db_execute($insert_query, [$date, $course_time, $student_id], 'sss');
        }
        
        // Rediriger pour éviter la soumission multiple
        header("Location: viewAttendance.php?success=1");
        exit();
    }
}

// Nous avons supprimé la partie concernant les élèves comme demandé

// Fonction utilitaire pour gérer les valeurs nulles avec htmlspecialchars
function safe_html($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Build the page content
$content = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Présences</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="JS/login_logout.js"></script>
    <style>
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-radius: 50%;
            border-top: 4px solid #0d6efd;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        body {
            background-color: #f8f9fa;
        }
        .table-responsive {
            overflow-x: auto;
        }
        /* Styles responsifs */
        @media (max-width: 768px) {
            .header-title {
                font-size: 1.2rem;
            }
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }
            .card-body {
                padding: 1rem;
            }
        }
        /* Amélioration des cartes */
        .attendance-card {
            transition: all 0.3s ease;
            height: 100%;
        }
        .attendance-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        /* Styles pour les listes de présence */
        .presence-list {
            min-height: 100px;
            max-height: 300px;
            overflow: auto;
            border-radius: 0.25rem;
        }
        .presence-list:empty::after {
            content: "Aucune donnée disponible";
            display: block;
            text-align: center;
            padding: 1rem;
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="bg-white shadow-sm mb-4">
        <div class="container py-3">
            <div class="row align-items-center">
                <div class="col-md-6 d-flex align-items-center mb-3 mb-md-0">
                    <img src="../../source/logo.jpg" class="me-3" width="48" height="48" alt="School Management System"/>
                    <h1 class="h3 fw-bold text-dark mb-0 header-title">Système de Gestion Scolaire</h1>
                </div>
                <div class="col-md-6 d-flex justify-content-md-end align-items-center">
                    <span class="me-3 d-none d-sm-inline">Bonjour, ' . htmlspecialchars($login_session) . '</span>
                    <a href="logout.php" class="btn btn-sm btn-danger">
                        <i class="fas fa-sign-out-alt me-2"></i><span class="d-none d-sm-inline">Déconnexion</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">
                            <i class="fas fa-home me-2"></i>Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="manageParent.php" class="nav-link">
                            <i class="fas fa-users me-2"></i>Gestion des Parents
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="teacherAttendance.php" class="nav-link">
                            <i class="fas fa-clipboard-check me-2"></i>Gestion des Présences
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Loading Spinner -->
    <div id="loading-spinner" class="loading-spinner d-none"></div>

    <div class="container py-4">
        <div class="mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h2 class="h3 fw-bold mb-2 mb-sm-0">Historique des Présences</h2>
                <div class="small text-muted bg-light py-1 px-2 rounded">
                    <i class="far fa-calendar-alt me-1"></i>Date: ' . date('d/m/Y') . '
                </div>
            </div>

            <!-- Teacher Attendance Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary bg-opacity-10 py-3">
                    <h3 class="h5 fw-semibold mb-0 text-primary"><i class="fas fa-chalkboard-teacher me-2"></i>Présences des Enseignants</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="teaid" class="form-label">Sélectionner un enseignant:</label>
                        <select id="teaid" name="teaid" class="form-select">';

// Get teachers for this admin
$stmt = $conn->prepare("SELECT id, name FROM teachers WHERE CAST(created_by AS CHAR) = CAST(? AS CHAR) ORDER BY name");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$teachers = $stmt->get_result();

if ($teachers->num_rows > 0) {
    $content .= '<option value="">Sélectionnez un enseignant</option>';
    while($teacher = $teachers->fetch_assoc()) {
        $content .= '<option value="'.htmlspecialchars($teacher['id']).'">'
                    .htmlspecialchars($teacher['name']).'</option>';
    }
} else {
    $content .= '<option value="">Aucun enseignant trouvé</option>';
}

$content .= '</select>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="card bg-light attendance-card">
                                <div class="card-header bg-success bg-opacity-10">
                                    <h4 class="h6 fw-medium mb-0 text-success"><i class="fas fa-calendar-check me-2"></i>Présences ce mois</h4>
                                </div>
                                <div class="card-body">
                                    <div id="myteapresent" class="presence-list"></div>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>

            <!-- Staff Attendance Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary bg-opacity-10 py-3">
                    <h3 class="h5 fw-semibold mb-0 text-primary"><i class="fas fa-user-tie me-2"></i>Présences du Personnel</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="staffid" class="form-label">Sélectionner un membre du personnel:</label>
                        <select id="staffid" name="staffid" class="form-select">';

// Get staff for this admin
$stmt = $conn->prepare("SELECT id, name FROM staff WHERE CAST(created_by AS CHAR) = CAST(? AS CHAR) ORDER BY name");
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$staff = $stmt->get_result();

if ($staff->num_rows > 0) {
    $content .= '<option value="">Sélectionnez un membre du personnel</option>';
    while($staff_member = $staff->fetch_assoc()) {
        $content .= '<option value="'.htmlspecialchars($staff_member['id']).'">'
                    .htmlspecialchars($staff_member['name']).'</option>';
    }
} else {
    $content .= '<option value="">Aucun membre du personnel trouvé</option>';
}

$content .= '</select>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="card bg-light attendance-card">
                                <div class="card-header bg-success bg-opacity-10">
                                    <h4 class="h6 fw-medium mb-0 text-success"><i class="fas fa-calendar-check me-2"></i>Présences ce mois</h4>
                                </div>
                                <div class="card-body">
                                    <div id="mystaffpresent" class="presence-list"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Modal de justification -->
    <div class="modal fade" id="justificationModal" tabindex="-1" aria-labelledby="justificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary bg-opacity-10">
                    <h5 class="modal-title" id="justificationModalLabel"><i class="fas fa-clipboard me-2"></i>Justifier l\'absence</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="justificationForm">
                        <input type="hidden" name="student_id" id="modal_student_id">
                        <input type="hidden" name="date" id="modal_date">
                        <input type="hidden" name="course_time" id="modal_course_time">
                        <input type="hidden" name="justify" value="1">
                        
                        <div class="mb-3">
                            <label for="modal_justification" class="form-label">Justification</label>
                            <textarea name="justification" id="modal_justification" rows="4" 
                                    class="form-control" required></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Script personnalisé pour la gestion des présences -->
    <script src="js/attendance.js"></script>
</body>
</html>';

$stmt->close();
$conn->close();

// Output the content
echo $content;
?>

