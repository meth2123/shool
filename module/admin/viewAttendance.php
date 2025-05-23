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
        $check_query = "SELECT id FROM attendance WHERE attendedid = ? AND DATE(date) = ? AND TIME(date) = ?";
        $existing = db_fetch_row($check_query, [$student_id, $date, $course_time], 'sss');
        
        if ($existing) {
            // Mettre à jour l'entrée existante
            $update_query = "UPDATE attendance SET date = CONCAT(?, ' ', ?) WHERE id = ?";
            db_execute($update_query, [$date, $course_time, $existing['id']], 'ssi');
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

// Récupérer toutes les absences
$query = "
SELECT 
    s.id as student_id,
    s.name as student_name,
    c.name as class_name,
    co.name as course_name,
    t.name as teacher_name,
    DATE_FORMAT(a.date, '%d/%m/%Y') as date,
    TIME(a.date) as course_time,
    CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END as is_present,
    NULL as remarks,
    NULL as updated_at,
    NULL as updated_by_name
FROM students s
JOIN class c ON s.classid = c.id
JOIN student_teacher_course stc ON s.id = stc.student_id
JOIN course co ON stc.course_id = co.id
JOIN teachers t ON stc.teacher_id = t.id
LEFT JOIN attendance a ON s.id = a.attendedid 
    AND DATE(a.date) = CURDATE()
WHERE DATE(a.date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
ORDER BY a.date DESC, TIME(a.date) ASC";

$absences = db_fetch_all($query);

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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="JS/login_logout.js"></script>
    <style>
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-radius: 50%;
            border-top: 4px solid #3498db;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <div class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="../../source/logo.jpg" class="h-16 w-16 object-contain mr-4" alt="School Management System"/>
                    <h1 class="text-2xl font-bold text-gray-800">Système de Gestion Scolaire</h1>
                </div>
                <div class="flex items-center">
                    <span class="mr-4">Bonjour, ' . htmlspecialchars($login_session) . '</span>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="bg-white shadow-md mt-4">
        <div class="container mx-auto px-4">
            <div class="flex space-x-4 py-4">
                <a href="index.php" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-home mr-2"></i>Accueil
                </a>
                <a href="manageParent.php" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-users mr-2"></i>Gestion des Parents
                </a>
            </div>
        </div>
    </nav>

    <!-- Loading Spinner -->
    <div id="loading-spinner" class="loading-spinner hidden"></div>

    <div class="container mx-auto px-4 py-8">
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-900">Historique des Présences</h2>
                <div class="text-sm text-gray-600">
                    Date: ' . date('d/m/Y') . '
                </div>
            </div>

            <!-- Teacher Attendance Section -->
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h3 class="text-xl font-semibold mb-4 text-blue-800">Présences des Enseignants</h3>
                <div class="mb-4">
                    <label for="teaid" class="block text-sm font-medium text-gray-700">Sélectionner un enseignant:</label>
                    <select id="teaid" name="teaid" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">';

// Get teachers for this admin
$stmt = $conn->prepare("SELECT id, name FROM teachers WHERE created_by = ? ORDER BY name");
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-700 mb-2">Présences ce mois</h4>
                        <div id="myteapresent" class="min-h-[100px] overflow-y-auto max-h-[300px]"></div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-700 mb-2">Absences ce mois</h4>
                        <div id="myteaabsent" class="min-h-[100px] overflow-y-auto max-h-[300px]"></div>
                    </div>
                </div>
            </div>

            <!-- Staff Attendance Section -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-xl font-semibold mb-4 text-blue-800">Présences du Personnel</h3>
                <div class="mb-4">
                    <label for="staffid" class="block text-sm font-medium text-gray-700">Sélectionner un membre du personnel:</label>
                    <select id="staffid" name="staffid" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">';

// Get staff for this admin
$stmt = $conn->prepare("SELECT id, name FROM staff WHERE created_by = ? ORDER BY name");
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-700 mb-2">Présences ce mois</h4>
                        <div id="mystaffpresent" class="min-h-[100px] overflow-y-auto max-h-[300px]"></div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-700 mb-2">Absences ce mois</h4>
                        <div id="mystaffabsent" class="min-h-[100px] overflow-y-auto max-h-[300px]"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestion des Absences</h1>
            </div>';

if (isset($_GET['success'])) {
    $content .= '
        <div class="mb-4 p-4 bg-green-50 text-green-700 rounded-lg">
            La justification a été enregistrée avec succès.
        </div>';
}

$content .= '
            <!-- Liste des absences -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Heure</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Élève</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Classe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Matière</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Professeur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">';

if (!empty($absences)) {
    foreach ($absences as $absence) {
        $status_class = $absence['is_present'] ? 'text-green-600' : 'text-red-600';
        $status_text = $absence['is_present'] ? 'Présent' : 'Absent';
        
        $content .= '
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . 
                    safe_html($absence['date']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . 
                    safe_html($absence['course_time']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . 
                    safe_html($absence['student_name']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . 
                    safe_html($absence['class_name']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . 
                    safe_html($absence['course_name']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . 
                    safe_html($absence['teacher_name']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm ' . $status_class . '">' . 
                    safe_html($status_text) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <button onclick="showJustificationModal(\'' . 
                        safe_html($absence['student_id']) . '\', \'' . 
                        safe_html($absence['date']) . '\', \'' . 
                        safe_html($absence['course_time']) . '\', \'' . 
                        safe_html($absence['remarks'] ?? '') . '\')"
                            class="text-blue-600 hover:text-blue-900">
                        ' . ($absence['is_present'] ? 'Modifier' : 'Marquer présent') . '
                    </button>
                </td>
            </tr>';
    }
} else {
    $content .= '
        <tr>
            <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                Aucune absence enregistrée pour les 30 derniers jours
            </td>
        </tr>';
}

$content .= '
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de justification -->
<div id="justificationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Justifier l\'absence</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="student_id" id="modal_student_id">
                <input type="hidden" name="date" id="modal_date">
                <input type="hidden" name="course_time" id="modal_course_time">
                <input type="hidden" name="justify" value="1">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Justification</label>
                    <textarea name="justification" id="modal_justification" rows="4" 
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                              required></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="hideJustificationModal()"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                        Annuler
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Show/hide loading spinner
    function toggleLoading(show) {
        document.getElementById("loading-spinner").style.display = show ? "block" : "none";
    }

    // Generic error handler for AJAX requests
    function handleAjaxError(elementId, error) {
        console.error("AJAX error:", error);
        document.getElementById(elementId).innerHTML = 
            \'<div class="p-4 text-red-500 bg-red-50 rounded-lg border border-red-200">\' +
            \'<p class="font-medium">Une erreur est survenue</p>\' +
            \'<p class="text-sm">\' + (error.message || "Erreur lors du chargement des données") + \'</p>\' +
            \'</div>\';
    }

    window.ajaxRequestToGetAttendanceTeacherPresentThisMonth = function() {
        var teacherId = document.getElementById("teaid").value;
        if (!teacherId) {
            console.log("No teacher selected");
            return;
        }
        
        toggleLoading(true);
        $.ajax({
            url: "myattendanceteacherthismonth.php",
            method: "POST",
            data: { id: teacherId },
            dataType: "json",
            success: function(response) {
                console.log("Teacher presence response:", response);
                var html = "";
                if (response.error) {
                    handleAjaxError("myteapresent", { message: response.error });
                } else if (response.records && response.records.length > 0) {
                    response.records.forEach(function(record) {
                        html += \'<div class="mb-2 p-2 text-green-600 border-b">\' +
                               new Date(record.date).toLocaleDateString("fr-FR") +
                               " - " + record.status + "</div>";
                    });
                } else {
                    html = \'<div class="text-gray-500 text-center">Aucune présence trouvée</div>\';
                }
                document.getElementById("myteapresent").innerHTML = html;
                ajaxRequestToGetAttendanceTeacherAbsentThisMonth();
            },
            error: function(xhr, status, error) {
                handleAjaxError("myteapresent", error);
            },
            complete: function() {
                toggleLoading(false);
            }
        });
    };

    window.ajaxRequestToGetAttendanceTeacherAbsentThisMonth = function() {
        var teacherId = document.getElementById("teaid").value;
        if (!teacherId) {
            console.log("No teacher selected");
            return;
        }
        
        toggleLoading(true);
        $.ajax({
            url: "myattendanceteacherabsentthismonth.php",
            method: "POST",
            data: { id: teacherId },
            dataType: "json",
            success: function(response) {
                console.log("Teacher absence response:", response);
                var html = "";
                if (response.error) {
                    handleAjaxError("myteaabsent", { message: response.error });
                } else if (response.records && response.records.length > 0) {
                    response.records.forEach(function(record) {
                        html += \'<div class="mb-2 p-2 text-red-600 border-b">\' +
                               new Date(record.date).toLocaleDateString("fr-FR") +
                               " - " + record.status + "</div>";
                    });
                } else {
                    html = \'<div class="text-gray-500 text-center">Aucune absence trouvée</div>\';
                }
                document.getElementById("myteaabsent").innerHTML = html;
            },
            error: function(xhr, status, error) {
                handleAjaxError("myteaabsent", error);
            },
            complete: function() {
                toggleLoading(false);
            }
        });
    };

    window.ajaxRequestToGetAttendanceStaffPresentThisMonth = function() {
        var staffId = document.getElementById("staffid").value;
        if (!staffId) {
            console.log("No staff member selected");
            return;
        }
        
        toggleLoading(true);
        $.ajax({
            url: "myattendancestaffthismonth.php",
            method: "POST",
            data: { id: staffId },
            dataType: "json",
            success: function(response) {
                console.log("Staff presence response:", response);
                var html = "";
                if (response.error) {
                    handleAjaxError("mystaffpresent", { message: response.error });
                } else if (response.records && response.records.length > 0) {
                    response.records.forEach(function(record) {
                        html += \'<div class="mb-2 p-2 text-green-600 border-b">\' +
                               new Date(record.date).toLocaleDateString("fr-FR") +
                               " - " + record.status + "</div>";
                    });
                } else {
                    html = \'<div class="text-gray-500 text-center">Aucune présence trouvée</div>\';
                }
                document.getElementById("mystaffpresent").innerHTML = html;
                ajaxRequestToGetAttendanceStaffAbsentThisMonth();
            },
            error: function(xhr, status, error) {
                handleAjaxError("mystaffpresent", error);
            },
            complete: function() {
                toggleLoading(false);
            }
        });
    };

    window.ajaxRequestToGetAttendanceStaffAbsentThisMonth = function() {
        var staffId = document.getElementById("staffid").value;
        if (!staffId) {
            console.log("No staff member selected");
            return;
        }
        
        toggleLoading(true);
        $.ajax({
            url: "myattendancestaffabsentthismonth.php",
            method: "POST",
            data: { id: staffId },
            dataType: "json",
            success: function(response) {
                console.log("Staff absence response:", response);
                var html = "";
                if (response.error) {
                    handleAjaxError("mystaffabsent", { message: response.error });
                } else if (response.records && response.records.length > 0) {
                    response.records.forEach(function(record) {
                        html += \'<div class="mb-2 p-2 text-red-600 border-b">\' +
                               new Date(record.date).toLocaleDateString("fr-FR") +
                               " - " + record.status + "</div>";
                    });
                } else {
                    html = \'<div class="text-gray-500 text-center">Aucune absence trouvée</div>\';
                }
                document.getElementById("mystaffabsent").innerHTML = html;
            },
            error: function(xhr, status, error) {
                handleAjaxError("mystaffabsent", error);
            },
            complete: function() {
                toggleLoading(false);
            }
        });
    };

    var teacherSelect = document.getElementById("teaid");
    var staffSelect = document.getElementById("staffid");

    if (teacherSelect) {
        teacherSelect.addEventListener("change", function() {
            console.log("Teacher changed:", this.value);
            if (this.value) {
                ajaxRequestToGetAttendanceTeacherPresentThisMonth();
            } else {
                document.getElementById("myteapresent").innerHTML = \'<div class="text-gray-500 text-center">Sélectionnez un enseignant</div>\';
                document.getElementById("myteaabsent").innerHTML = \'<div class="text-gray-500 text-center">Sélectionnez un enseignant</div>\';
            }
        });

        // Initialize with first teacher if any
        if (teacherSelect.options.length > 1) {
            teacherSelect.selectedIndex = 1;
            ajaxRequestToGetAttendanceTeacherPresentThisMonth();
        }
    }

    if (staffSelect) {
        staffSelect.addEventListener("change", function() {
            console.log("Staff changed:", this.value);
            if (this.value) {
                ajaxRequestToGetAttendanceStaffPresentThisMonth();
            } else {
                document.getElementById("mystaffpresent").innerHTML = \'<div class="text-gray-500 text-center">Sélectionnez un membre du personnel</div>\';
                document.getElementById("mystaffabsent").innerHTML = \'<div class="text-gray-500 text-center">Sélectionnez un membre du personnel</div>\';
            }
        });

        // Initialize with first staff member if any
        if (staffSelect.options.length > 1) {
            staffSelect.selectedIndex = 1;
            ajaxRequestToGetAttendanceStaffPresentThisMonth();
        }
    }

    console.log("Event listeners and initialization completed");
});

function showJustificationModal(studentId, date, time, justification) {
    document.getElementById("modal_student_id").value = studentId;
    document.getElementById("modal_date").value = date;
    document.getElementById("modal_course_time").value = time;
    document.getElementById("modal_justification").value = justification;
    document.getElementById("justificationModal").classList.remove("hidden");
}

function hideJustificationModal() {
    document.getElementById("justificationModal").classList.add("hidden");
}
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .container, .container * {
        visibility: visible;
    }
    .container {
        position: absolute;
        left: 0;
        top: 0;
    }
    button, #justificationModal {
        display: none;
    }
}
</style>
</body>
</html>';

$stmt->close();
$conn->close();

// Output the content
echo $content;
?>

