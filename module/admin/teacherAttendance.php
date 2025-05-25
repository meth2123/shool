<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

$admin_id = $_SESSION['login_id'];

// Approche en deux étapes pour éviter les problèmes de collation

// 1. D'abord, récupérer tous les enseignants avec toutes les colonnes nécessaires
// Note: La colonne 'subject' n'existe pas dans la table teachers
$sql_teachers = "SELECT t.id, t.name, t.phone, t.email, 
                 GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') AS classes
                 FROM teachers t
                 LEFT JOIN course co ON CAST(co.teacherid AS CHAR) = CAST(t.id AS CHAR)
                 LEFT JOIN class c ON CAST(co.classid AS CHAR) = CAST(c.id AS CHAR)
                 GROUP BY t.id, t.name, t.phone, t.email";
$all_teachers = $link->query($sql_teachers);

// Tableau pour stocker les enseignants filtrés
$filtered_teachers = [];

// 2. Filtrer manuellement les enseignants qui correspondent à l'administrateur connecté
if ($all_teachers && $all_teachers->num_rows > 0) {
    while ($teacher = $all_teachers->fetch_assoc()) {
        // Vérifier si l'enseignant a été créé par l'administrateur actuel
        $check_sql = "SELECT id FROM teachers WHERE id = '" . $link->real_escape_string($teacher['id']) . "' AND created_by = '" . $link->real_escape_string($admin_id) . "'";
        $check_result = $link->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            // Vérifier si l'enseignant a déjà été marqué présent aujourd'hui
            $attendance_sql = "SELECT id FROM attendance WHERE attendedid = '" . $link->real_escape_string($teacher['id']) . "' AND date = CURDATE()";
            $attendance_result = $link->query($attendance_sql);
            
            // Vérifier si l'enseignant a déjà été marqué absent aujourd'hui
            $absence_sql = "SELECT id FROM teacher_absences WHERE teacher_id = '" . $link->real_escape_string($teacher['id']) . "' AND date = CURDATE()";
            $absence_result = $link->query($absence_sql);
            
            // Si l'enseignant n'a pas été marqué présent ou absent, l'ajouter à la liste
            if ((!$attendance_result || $attendance_result->num_rows == 0) && 
                (!$absence_result || $absence_result->num_rows == 0)) {
                // S'assurer que toutes les clés existent pour éviter les avertissements
                if (!isset($teacher['phone'])) $teacher['phone'] = '';
                if (!isset($teacher['email'])) $teacher['email'] = '';
                if (!isset($teacher['classes'])) $teacher['classes'] = 'Aucune classe';
                
                $filtered_teachers[] = $teacher;
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
$result = new MockResult($filtered_teachers);

// Générer le contenu HTML
$content = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Présences des Enseignants</title>
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
                    <h2 class="h3 fw-bold">Présences des Enseignants</h2>
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
                                        <th>Classes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <div id="status-message" class="alert d-none mb-3"></div>';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $content .= '
        <tr>
            <td>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success btn-sm mark-attendance" data-id="' . htmlspecialchars($row['id']) . '" data-status="present">
                        Présent
                    </button>
                    <button type="button" class="btn btn-danger btn-sm mark-attendance" data-id="' . htmlspecialchars($row['id']) . '" data-status="absent">
                        Absent
                    </button>
                </div>
            </td>
            <td>' . htmlspecialchars($row['id']) . '</td>
            <td>' . htmlspecialchars($row['name']) . '</td>
            <td>' . htmlspecialchars($row['phone'] ?? '') . '</td>
            <td>' . htmlspecialchars($row['email'] ?? '') . '</td>
            <td>' . htmlspecialchars($row['classes'] ?? 'Aucune classe') . '</td>
        </tr>';
    }
} else {
    $content .= '
    <tr>
        <td colspan="6" class="text-center text-muted">
            Tous les enseignants ont été marqués pour aujourd\'hui
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
                window.location.href = "teacherAttendance.php";
            }, 3000);
        }
    </script>';

$content .= '<script>
    // Gestion des boutons de présence/absence avec AJAX
    document.addEventListener("DOMContentLoaded", function() {
        const statusMessage = document.getElementById("status-message");
        
        // Ajouter des écouteurs d\'\u00e9vénements à tous les boutons de présence/absence
        document.querySelectorAll(".mark-attendance").forEach(button => {
            button.addEventListener("click", function() {
                const teacherId = this.getAttribute("data-id");
                const status = this.getAttribute("data-status");
                const row = this.closest("tr");
                
                // Désactiver les boutons pendant le traitement
                const buttons = row.querySelectorAll("button");
                buttons.forEach(btn => btn.disabled = true);
                
                // Afficher un indicateur de chargement
                statusMessage.textContent = "Traitement en cours...";
                statusMessage.className = "alert alert-info mb-3";
                
                // Envoyer la requête AJAX
                fetch("attendTeacher.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: "ajax=1&id=" + encodeURIComponent(teacherId) + "&status=" + encodeURIComponent(status)
                })
                .then(response => response.json())
                .then(data => {
                    // Afficher le message de réponse
                    statusMessage.textContent = data.message;
                    statusMessage.className = data.success ? "alert alert-success mb-3" : "alert alert-danger mb-3";
                    
                    // Si succès, supprimer la ligne du tableau après un court délai
                    if (data.success) {
                        setTimeout(() => {
                            row.style.transition = "opacity 0.5s";
                            row.style.opacity = "0";
                            setTimeout(() => {
                                row.remove();
                                
                                // Vérifier s\'il reste des enseignants dans le tableau
                                const remainingRows = document.querySelectorAll("tbody tr");
                                if (remainingRows.length === 0) {
                                    const tbody = document.querySelector("tbody");
                                    tbody.innerHTML = "<tr><td colspan=\"6\" class=\"text-center text-muted\">Tous les enseignants ont été marqués pour aujourd\'hui</td></tr>";
                                }
                                
                                // Masquer le message après un délai
                                setTimeout(() => {
                                    statusMessage.className = "alert d-none mb-3";
                                }, 3000);
                            }, 500);
                        }, 1000);
                    } else {
                        // Réactiver les boutons en cas d\'erreur
                        buttons.forEach(btn => btn.disabled = false);
                    }
                })
                .catch(error => {
                    console.error("Erreur:", error);
                    statusMessage.textContent = "Erreur de communication avec le serveur";
                    statusMessage.className = "alert alert-danger mb-3";
                    // Réactiver les boutons en cas d\'erreur
                    buttons.forEach(btn => btn.disabled = false);
                });
            });
        });
    });
</script>
</body>
</html>';

include('templates/layout.php');
?>
