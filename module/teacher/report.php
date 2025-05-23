<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification de la session
if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$teacher_id = $_SESSION['login_id'];

// Traiter la soumission des présences
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $course_id = $_POST['course_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $course_time = $_POST['course_time'] ?? '';
    $attendance_data = $_POST['attendance'] ?? [];
    
    if ($course_id && $date && $course_time && !empty($attendance_data)) {
        // Vérifier que le cours appartient bien à l'enseignant
        $check_query = "SELECT 1 FROM student_teacher_course 
                       WHERE teacher_id = ? AND course_id = ?";
        $check_result = db_fetch_one($check_query, [$teacher_id, $course_id], 'ss');
        
        if ($check_result) {
            // Supprimer les anciennes présences pour cette date et ce cours
            $delete_query = "DELETE FROM attendance 
                           WHERE DATE(date) = ? 
                           AND TIME(date) = ? 
                           AND attendedid IN (
                               SELECT student_id 
                               FROM student_teacher_course 
                               WHERE course_id = ?
                           )";
            db_execute($delete_query, [$date, $course_time, $course_id], 'sss');
            
            // Insérer les nouvelles présences
            foreach ($attendance_data as $student_id => $status) {
                if ($status === 'present') {
                    $insert_query = "INSERT INTO attendance (attendedid, date) 
                                   VALUES (?, CONCAT(?, ' ', ?))";
                    db_execute($insert_query, [$student_id, $date, $course_time], 'sss');
                }
            }
            
            // Rediriger pour éviter la soumission multiple
            header("Location: report.php?success=1");
            exit();
        }
    }
}

// Récupérer les cours de l'enseignant
$courses_query = "
SELECT DISTINCT 
    c.id as course_id,
    c.name as course_name,
    cl.name as class_name,
    COUNT(DISTINCT stc.student_id) as student_count
FROM course c
JOIN student_teacher_course stc ON c.id = stc.course_id
JOIN students s ON stc.student_id = s.id
JOIN class cl ON s.classid = cl.id
WHERE stc.teacher_id = ?
GROUP BY c.id, c.name, cl.name
ORDER BY c.name";

$courses = db_fetch_all($courses_query, [$teacher_id], 's');

// Fonction utilitaire pour gérer les valeurs nulles avec htmlspecialchars
function safe_html($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestion des Présences</h1>
        </div>';

if (isset($_GET['success'])) {
    $content .= '
        <div class="mb-4 p-4 bg-green-50 text-green-700 rounded-lg">
            Les présences ont été enregistrées avec succès.
        </div>';
}

// Liste des cours
$content .= '
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">';

foreach ($courses as $course) {
    $content .= '
        <div class="bg-white border rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">' . 
                safe_html($course['course_name']) . '</h3>
            <p class="text-sm text-gray-600 mb-4">Classe : ' . 
                safe_html($course['class_name']) . '</p>
            <p class="text-sm text-gray-600 mb-4">Nombre d\'élèves : ' . 
                safe_html($course['student_count']) . '</p>
            <button onclick="showAttendanceModal(\'' . 
                safe_html($course['course_id']) . '\', \'' . 
                safe_html($course['course_name']) . '\', \'' . 
                safe_html($course['class_name']) . '\')"
                    class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                Marquer les présences
            </button>
        </div>';
}

$content .= '
						    </div>

        <!-- Historique des présences -->
        <div class="mt-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Historique des Présences</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Heure</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cours</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Classe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Présents</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Absents</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">';

// Récupérer l'historique des présences
$history_query = "
SELECT 
    DATE_FORMAT(a.date, '%d/%m/%Y') as date,
    TIME(a.date) as course_time,
    c.name as course_name,
    cl.name as class_name,
    COUNT(DISTINCT CASE WHEN a.attendedid IS NOT NULL THEN a.attendedid END) as present_count,
    COUNT(DISTINCT stc.student_id) - COUNT(DISTINCT CASE WHEN a.attendedid IS NOT NULL THEN a.attendedid END) as absent_count
FROM student_teacher_course stc
JOIN course c ON stc.course_id = c.id
JOIN students s ON stc.student_id = s.id
JOIN class cl ON s.classid = cl.id
LEFT JOIN attendance a ON a.attendedid = stc.student_id 
    AND DATE(a.date) = CURDATE()
WHERE stc.teacher_id = ?
GROUP BY DATE(a.date), TIME(a.date), c.name, cl.name
ORDER BY a.date DESC, TIME(a.date) ASC
LIMIT 10";

$history = db_fetch_all($history_query, [$teacher_id], 's');

if (!empty($history)) {
    foreach ($history as $record) {
        $content .= '
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . 
                    safe_html($record['date']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . 
                    safe_html($record['course_time']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . 
                    safe_html($record['course_name']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . 
                    safe_html($record['class_name']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">' . 
                    safe_html($record['present_count']) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">' . 
                    safe_html($record['absent_count']) . '</td>
            </tr>';
    }
} else {
    $content .= '
        <tr>
            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                Aucun historique de présence disponible
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

<!-- Modal de saisie des présences -->
<div id="attendanceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Marquer les présences</h3>
                <button onclick="hideAttendanceModal()" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <form method="POST" class="space-y-4" id="attendanceForm">
                <input type="hidden" name="course_id" id="modal_course_id">
                <input type="hidden" name="mark_attendance" value="1">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date</label>
                        <input type="date" name="date" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               value="' . date('Y-m-d') . '">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Heure du cours</label>
                        <select name="course_time" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="08:00:00">8h00 - 9h00</option>
                            <option value="09:00:00">9h00 - 10h00</option>
                            <option value="10:00:00">10h00 - 11h00</option>
                            <option value="11:00:00">11h00 - 12h00</option>
                            <option value="14:00:00">14h00 - 15h00</option>
                            <option value="15:00:00">15h00 - 16h00</option>
                            <option value="16:00:00">16h00 - 17h00</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Élève</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Présence</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="studentsList">
                                <!-- La liste des élèves sera chargée dynamiquement -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="hideAttendanceModal()"
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

<script>
function showAttendanceModal(courseId, courseName, className) {
    document.getElementById("modal_course_id").value = courseId;
    document.getElementById("modalTitle").textContent = 
        "Marquer les présences - " + courseName + " (" + className + ")";
    
    // Charger la liste des élèves
    fetch("get_students.php?course_id=" + courseId)
        .then(response => response.json())
        .then(data => {
            const studentsList = document.getElementById("studentsList");
            studentsList.innerHTML = "";
            
            data.forEach(student => {
                const row = document.createElement("tr");
                row.innerHTML = 
                    "<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900\">" +
                        student.name +
                    "</td>" +
                    "<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900\">" +
                        "<label class=\"inline-flex items-center\">" +
                            "<input type=\"radio\" name=\"attendance[" + student.id + "]\" " +
                            "value=\"present\" class=\"form-radio text-blue-600\" checked>" +
                            "<span class=\"ml-2\">Présent</span>" +
                        "</label>" +
                        "<label class=\"inline-flex items-center ml-4\">" +
                            "<input type=\"radio\" name=\"attendance[" + student.id + "]\" " +
                            "value=\"absent\" class=\"form-radio text-blue-600\">" +
                            "<span class=\"ml-2\">Absent</span>" +
                        "</label>" +
                    "</td>";
                studentsList.appendChild(row);
            });
        })
        .catch(error => {
            console.error("Error loading students:", error);
            document.getElementById("studentsList").innerHTML = 
                "<tr><td colspan=\"2\" class=\"px-6 py-4 text-center text-sm text-red-500\">" +
                "Erreur lors du chargement des élèves</td></tr>";
        });
    
    document.getElementById("attendanceModal").classList.remove("hidden");
}

function hideAttendanceModal() {
    document.getElementById("attendanceModal").classList.add("hidden");
}

// Empêcher la soumission du formulaire si la date est dans le futur
document.getElementById("attendanceForm").addEventListener("submit", function(e) {
    const dateInput = this.querySelector("input[name=\"date\"]");
    const selectedDate = new Date(dateInput.value);
    const today = new Date();
    
    if (selectedDate > today) {
        e.preventDefault();
        alert("Vous ne pouvez pas marquer les présences pour une date future.");
    }
});
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
    button, #attendanceModal {
        display: none;
    }
}
</style>';

include('templates/layout.php');
?>
