<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

$admin_id = $_SESSION['login_id'];

// Approche en deux étapes pour éviter les problèmes de collation

// 1. D'abord, récupérer tous les membres du personnel avec toutes les colonnes nécessaires
$sql_staff = "SELECT id, name, phone, email FROM staff";
$all_staff = $link->query($sql_staff);

// Tableau pour stocker le personnel filtré
$filtered_staff = [];

// 2. Filtrer manuellement le personnel qui correspond à l'administrateur connecté
if ($all_staff && $all_staff->num_rows > 0) {
    while ($staff = $all_staff->fetch_assoc()) {
        // Vérifier si le membre du personnel a été créé par l'administrateur actuel
        $check_sql = "SELECT id FROM staff WHERE id = '" . $link->real_escape_string($staff['id']) . "' AND created_by = '" . $link->real_escape_string($admin_id) . "'";
        $check_result = $link->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            // Vérifier si le membre du personnel a déjà été marqué présent aujourd'hui
            $attendance_sql = "SELECT id FROM attendance WHERE attendedid = '" . $link->real_escape_string($staff['id']) . "' AND date = CURDATE()";
            $attendance_result = $link->query($attendance_sql);
            
            // Si le membre du personnel n'a pas été marqué présent, l'ajouter à la liste
            if (!$attendance_result || $attendance_result->num_rows == 0) {
                // S'assurer que toutes les clés existent pour éviter les avertissements
                if (!isset($staff['phone'])) $staff['phone'] = '';
                if (!isset($staff['email'])) $staff['email'] = '';
                
                $filtered_staff[] = $staff;
            }
        }
    }
}

// Créer un objet qui simule le résultat d'une requête pour maintenir la compatibilité avec le reste du code
class MockResult {
    public $num_rows;
    private $data;
    private $position = 0;
    
    public function __construct($data) {
        $this->data = $data;
        $this->num_rows = count($data);
    }
    
    public function fetch_assoc() {
        if ($this->position >= count($this->data)) {
            return null;
        }
        return $this->data[$this->position++];
    }
}

// Créer le résultat simulé
$result = new MockResult($filtered_staff);

// Générer le contenu HTML
$content = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Présences du Personnel</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h3 fw-bold">Présences du Personnel</h2>
                    <div class="text-muted small">
                        Date: ' . date('d/m/Y') . '
                    </div>
                </div>

                ' . (isset($_GET['success']) ? '<div class="alert alert-success mb-4">' . htmlspecialchars($_GET['success']) . '</div>' : '') . '
                ' . (isset($_GET['error']) ? '<div class="alert alert-danger mb-4">' . htmlspecialchars($_GET['error']) . '</div>' : '') . '

                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Téléphone</th>
                                        <th>Email</th>
                                    </tr>
                                </thead>
                                <tbody>';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $content .= '
        <tr>
            <td>
                <div class="d-flex gap-2">
                    <form action="attendStaff.php" method="post">
                        <input type="hidden" value="' . htmlspecialchars($row['id']) . '" name="id">
                        <button type="submit" name="submit" value="present" class="btn btn-success btn-sm">
                            Présent
                        </button>
                    </form>
                    <form action="attendStaff.php" method="post">
                        <input type="hidden" value="' . htmlspecialchars($row['id']) . '" name="id">
                        <button type="submit" name="submit" value="absent" class="btn btn-danger btn-sm">
                            Absent
                        </button>
                    </form>
                </div>
            </td>
            <td>' . htmlspecialchars($row['id']) . '</td>
            <td>' . htmlspecialchars($row['name']) . '</td>
            <td>' . htmlspecialchars($row['phone'] ?? '') . '</td>
            <td>' . htmlspecialchars($row['email'] ?? '') . '</td>
        </tr>';
    }
} else {
    $content .= '
        <tr>
            <td colspan="5" class="text-center text-muted">
                Tous les membres du personnel ont été marqués pour aujourd\'hui
            </td>
        </tr>';
}

$content .= '
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Redirection après un délai si un message de succès est présent
        if (document.querySelector(".alert-success")) {
            setTimeout(function() {
                window.location.href = "staffAttendance.php";
            }, 3000);
        }
    </script>
</body>
</html>';

include('templates/layout.php');
?>
