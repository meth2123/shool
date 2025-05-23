<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification de la session
if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Connexion à la base de données
require_once('../../db/config.php');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$teacher_id = $_SESSION['login_id'];
$course_id = $_GET['course_id'] ?? '';

if (empty($course_id)) {
    header("Location: exam.php");
    exit();
}

// Traitement du formulaire d'appel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance'])) {
    if (!isset($_POST['date']) || !isset($_POST['course_id']) || !isset($_POST['course_time'])) {
        echo "<script>alert('Erreur: informations manquantes');</script>";
    } else {
        $date = $_POST['date'];
        $course_id = $_POST['course_id'];
        $course_time = $_POST['course_time'];
        $attendance_data = $_POST['attendance'];
        
        // Supprimer les anciens enregistrements pour cette date, cet étudiant et ce créneau horaire
        $delete_query = "DELETE FROM attendance WHERE DATE(date) = ? AND attendedid = ? AND TIME(date) = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("sss", $date, $student_id, $course_time);
        $stmt->execute();
        
        // Insérer les nouveaux enregistrements
        $insert_query = "INSERT INTO attendance (date, attendedid) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_query);
        
        foreach($attendance_data as $student_id => $status) {
            // On insère uniquement les présences
            if ($status === 'present') {
                // Combiner la date et l'heure pour le champ date
                $date_time = $date . ' ' . $course_time;
                $stmt->bind_param("ss", $date_time, $student_id);
                $stmt->execute();
            }
        }
        
        echo "<script>alert('Présence enregistrée avec succès!');</script>";
    }
}

// Récupérer les détails de l'examen
$exam = db_fetch_row(
    "SELECT e.*, 
            c.name as course_name,
            c.id as course_id,
            cl.name as class_name,
            cl.id as class_id,
            DATE_FORMAT(e.examdate, '%d/%m/%Y') as formatted_date,
            TIME_FORMAT(e.time, '%H:%i') as formatted_time,
            CASE 
                WHEN e.examdate < CURDATE() THEN 'past'
                WHEN e.examdate = CURDATE() THEN 'today'
                ELSE 'upcoming'
            END as status
     FROM examschedule e
     JOIN course c ON e.courseid = c.id
     JOIN class cl ON c.classid = cl.id
     WHERE c.id = ? AND c.teacherid = ?",
    [$course_id, $teacher_id],
    'ss'
);

if (!$exam) {
    header("Location: exam.php?error=exam_not_found");
    exit();
}

// Récupérer la liste des étudiants inscrits à ce cours
$students = db_fetch_all(
    "SELECT DISTINCT s.id, s.name, s.email, s.phone, s.sex, s.dob, s.addmissiondate, s.address, s.parentid, s.classid,
            (SELECT COUNT(*) FROM attendance a 
             WHERE a.attendedid = s.id 
             AND DATE(a.date) = CURDATE()) as is_present
     FROM students s
     INNER JOIN student_teacher_course stc ON s.id = stc.student_id
     WHERE stc.course_id = ? 
     AND stc.teacher_id = ?
     ORDER BY s.name",
    [$course_id, $teacher_id],
    'ss'
);

// Récupérer les statistiques de présence pour aujourd'hui
$today_stats = db_fetch_row(
    "WITH unique_students AS (
        SELECT DISTINCT student_id
        FROM student_teacher_course
        WHERE course_id = ?
    ),
    attendance_stats AS (
        SELECT 
            s.id,
            CASE WHEN a.attendedid IS NOT NULL THEN 1 ELSE 0 END as is_present
        FROM students s
        INNER JOIN unique_students us ON s.id = us.student_id
        LEFT JOIN attendance a ON s.id = a.attendedid 
            AND DATE(a.date) = CURDATE()
            AND TIME(a.date) = ?
    )
    SELECT 
        COUNT(*) as total_students,
        SUM(is_present) as present_count,
        COUNT(*) - SUM(is_present) as absent_count
    FROM attendance_stats",
    [$course_id, $_POST['course_time'] ?? '08:00:00'],
    'ss'
);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appel - <?php echo htmlspecialchars($exam['course_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <img src="../../source/logo.jpg" class="h-12 w-12 object-contain" alt="School Management System"/>
                    <h1 class="ml-4 text-xl font-semibold text-gray-800">Appel - <?php echo htmlspecialchars($exam['course_name']); ?></h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="exam.php" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left mr-2"></i>Retour
                    </a>
                    <a href="index.php" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-home mr-2"></i>Accueil
                    </a>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Message de succès -->
    <?php if (isset($_GET['success'])): ?>
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
            <p class="font-bold">Succès</p>
            <p>L'appel a été enregistré avec succès.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Contenu principal -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- En-tête de l'examen -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        <?php echo htmlspecialchars($exam['course_name']); ?>
                    </h2>
                    <p class="text-gray-600">
                        <i class="fas fa-chalkboard-teacher mr-2"></i>
                        <?php echo htmlspecialchars($exam['class_name']); ?>
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="px-3 py-1 rounded-full text-sm font-medium 
                        <?php echo $exam['status'] === 'upcoming' ? 'bg-green-100 text-green-800' : 
                                ($exam['status'] === 'today' ? 'bg-yellow-100 text-yellow-800' : 
                                'bg-gray-100 text-gray-800'); ?>">
                        <i class="fas <?php echo $exam['status'] === 'upcoming' ? 'fa-calendar-plus' : 
                                            ($exam['status'] === 'today' ? 'fa-calendar-day' : 
                                            'fa-calendar-check'); ?> mr-2"></i>
                        <?php echo $exam['status'] === 'upcoming' ? 'À venir' : 
                                ($exam['status'] === 'today' ? 'Aujourd\'hui' : 'Passé'); ?>
                    </span>
                </div>
            </div>
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-calendar text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Date</p>
                        <p class="text-lg font-semibold text-gray-900"><?php echo date('d/m/Y'); ?></p>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Heure</p>
                        <p class="text-lg font-semibold text-gray-900"><?php echo date('H:i'); ?></p>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Étudiants</p>
                        <p class="text-lg font-semibold text-gray-900"><?php echo count($students); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques de présence -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Statistiques de présence aujourd'hui</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 rounded-lg p-4">
                    <p class="text-sm text-blue-600">Total</p>
                    <p class="text-2xl font-bold text-blue-700"><?php echo $today_stats['total_students']; ?></p>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <p class="text-sm text-green-600">Présents</p>
                    <p class="text-2xl font-bold text-green-700"><?php echo $today_stats['present_count']; ?></p>
                    <p class="text-xs text-green-600 mt-1">
                        <?php echo round(($today_stats['present_count'] / $today_stats['total_students']) * 100); ?>% de présence
                    </p>
                </div>
                <div class="bg-red-50 rounded-lg p-4">
                    <p class="text-sm text-red-600">Absents</p>
                    <p class="text-2xl font-bold text-red-700"><?php echo $today_stats['absent_count']; ?></p>
                    <p class="text-xs text-red-600 mt-1">
                        <?php echo round(($today_stats['absent_count'] / $today_stats['total_students']) * 100); ?>% d'absence
                    </p>
                </div>
            </div>
        </div>

        <!-- Formulaire d'appel -->
        <form method="POST" class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6">
                <!-- Ajouter les champs cachés pour la date et le course_id -->
                <input type="hidden" name="date" value="<?php echo date('Y-m-d'); ?>">
                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">
                <div class="mb-4">
                    <label for="course_time" class="block text-sm font-medium text-gray-700">Heure du cours</label>
                    <select name="course_time" id="course_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="updateAttendanceStats(this.value)">
                        <option value="08:00:00">8h00 - 9h00</option>
                        <option value="09:00:00">9h00 - 10h00</option>
                        <option value="10:00:00">10h00 - 11h00</option>
                        <option value="11:00:00">11h00 - 12h00</option>
                        <option value="14:00:00">14h00 - 15h00</option>
                        <option value="15:00:00">15h00 - 16h00</option>
                        <option value="16:00:00">16h00 - 17h00</option>
                    </select>
                </div>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Liste des étudiants</h3>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                        <i class="fas fa-save mr-2"></i>Enregistrer l'appel
                    </button>
                </div>
                <?php if (empty($students)): ?>
                    <p class="text-gray-500 text-center py-4">Aucun étudiant inscrit à ce cours</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($students as $student): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                        <i class="fas fa-user text-gray-500"></i>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($student['name']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        ID: <?php echo htmlspecialchars($student['id']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($student['is_present'] > 0): ?>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                    <i class="fas fa-check mr-1"></i>Présent
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                                    <i class="fas fa-times mr-1"></i>Absent
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center space-x-4">
                                                <label class="inline-flex items-center">
                                                    <input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="present" 
                                                           class="form-radio text-blue-600" 
                                                           <?php echo $student['is_present'] > 0 ? 'checked' : ''; ?>>
                                                    <span class="ml-2 text-sm text-gray-700">Présent</span>
                                                </label>
                                                <label class="inline-flex items-center">
                                                    <input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="absent" 
                                                           class="form-radio text-red-600"
                                                           <?php echo $student['is_present'] == 0 ? 'checked' : ''; ?>>
                                                    <span class="ml-2 text-sm text-gray-700">Absent</span>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Footer -->
    <footer class="bg-white shadow-lg mt-8">
        <div class="max-w-7xl mx-auto py-4 px-4">
            <p class="text-center text-gray-500 text-sm">
                © <?php echo date('Y'); ?> Système de Gestion Scolaire. Tous droits réservés.
            </p>
        </div>
    </footer>

    <script>
        // Ajouter une fonction pour mettre à jour les statistiques en fonction de l'heure
        function updateAttendanceStats(time) {
            // Faire une requête AJAX pour obtenir les nouvelles statistiques
            fetch('get_attendance_stats.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'course_id=<?php echo $course_id; ?>&course_time=' + time
            })
            .then(response => response.json())
            .then(data => {
                // Mettre à jour l'affichage des statistiques
                document.getElementById('total_students').textContent = data.total_students;
                document.getElementById('present_count').textContent = data.present_count;
                document.getElementById('absent_count').textContent = data.absent_count;
                
                // Mettre à jour les pourcentages
                const presentPercent = Math.round((data.present_count / data.total_students) * 100);
                const absentPercent = Math.round((data.absent_count / data.total_students) * 100);
                
                document.getElementById('present_percent').textContent = presentPercent + '% de présence';
                document.getElementById('absent_percent').textContent = absentPercent + '% d\'absence';
            });
        }

        // Ajouter des styles pour les boutons radio
        document.addEventListener('DOMContentLoaded', function() {
            const radioButtons = document.querySelectorAll('input[type="radio"]');
            radioButtons.forEach(radio => {
                radio.addEventListener('change', function() {
                    const row = this.closest('tr');
                    const statusCell = row.querySelector('td:nth-child(2)');
                    if (this.value === 'present') {
                        statusCell.innerHTML = '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800"><i class="fas fa-check mr-1"></i>Présent</span>';
                    } else {
                        statusCell.innerHTML = '<span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800"><i class="fas fa-times mr-1"></i>Absent</span>';
                    }
                });
            });
        });
    </script>
</body>
</html> 