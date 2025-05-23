<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification de l'ID de l'élève
$student_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$student_id) {
    header("Location: checkchild.php?error=no_student_selected");
    exit();
}

// Vérification que l'élève appartient bien au parent connecté
$student = db_fetch_row(
    "SELECT s.*, c.name as class_name 
     FROM students s 
     LEFT JOIN class c ON s.classid = c.id
     WHERE s.id = ? AND s.parentid = ?",
    [$student_id, $check],
    'ss'
);

if (!$student) {
    header("Location: checkchild.php?error=unauthorized");
    exit();
}

// Récupération des cours et notes de l'élève
$courses = db_fetch_all(
    "SELECT c.id as course_id, c.name as course_name, 
            t.name as teacher_name, t.email as teacher_email,
            g.grade, g.id as grade_id
     FROM course c
     LEFT JOIN teachers t ON c.teacherid = t.id
     LEFT JOIN grade g ON c.id = g.courseid AND g.studentid = ?
     WHERE c.studentid = ?
     ORDER BY c.name",
    [$student_id, $student_id],
    'ss'
);

// Fonction pour convertir une note en valeur numérique
function convertGradeToNumeric($grade) {
    $grade = strtoupper(trim($grade));
    $gradeMap = [
        'A+' => 20, 'A' => 18, 'A-' => 16,
        'B+' => 15, 'B' => 14, 'B-' => 13,
        'C+' => 12, 'C' => 11, 'C-' => 10,
        'D+' => 9, 'D' => 8, 'D-' => 7,
        'F' => 0
    ];
    return $gradeMap[$grade] ?? 0;
}

// Calcul de la moyenne générale
$total_grade = 0;
$total_courses = 0;
foreach ($courses as $course) {
    if (!empty($course['grade'])) {
        $total_grade += convertGradeToNumeric($course['grade']);
        $total_courses++;
    }
}
$average = $total_courses > 0 ? $total_grade / $total_courses : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cours et Notes - <?php echo htmlspecialchars($student['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <span class="mr-4">Bonjour, <?php echo htmlspecialchars($login_session); ?></span>
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
                <a href="checkchild.php" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-child mr-2"></i>Mes Enfants
                </a>
                <a href="childattendance.php?id=<?php echo $student_id; ?>" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-calendar-check mr-2"></i>Présences
                </a>
                <a href="childreport.php?id=<?php echo $student_id; ?>" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-file-alt mr-2"></i>Bulletin
                </a>
                <a href="childpayment.php?id=<?php echo $student_id; ?>" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-money-bill mr-2"></i>Paiements
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <!-- En-tête de l'élève -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        <?php echo htmlspecialchars($student['name']); ?>
                    </h2>
                    <p class="text-gray-600">
                        Classe : <?php echo htmlspecialchars($student['class_name']); ?>
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-600">Moyenne générale</div>
                    <div class="text-3xl font-bold <?php echo $average >= 10 ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo number_format($average, 2); ?>/20
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des cours -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Cours et Notes</h3>
            </div>
            
            <?php if (empty($courses)): ?>
                <div class="p-6 text-center text-gray-500">
                    Aucun cours n'est actuellement assigné à cet élève.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Cours
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Enseignant
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Note
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Équivalence
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($courses as $course): 
                                $grade_value = convertGradeToNumeric($course['grade']);
                                $grade_color = $grade_value >= 10 ? 'text-green-600' : 
                                             ($grade_value >= 8 ? 'text-yellow-600' : 'text-red-600');
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($course['course_name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($course['teacher_name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($course['teacher_email']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $grade_color; ?>">
                                            <?php echo htmlspecialchars($course['grade'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $course['grade'] ? number_format($grade_value, 2) . '/20' : 'N/A'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Légende des notes -->
        <div class="mt-6 bg-white rounded-lg shadow-lg p-6">
            <h4 class="text-lg font-semibold text-gray-900 mb-4">Légende des notes</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="flex items-center">
                    <span class="w-4 h-4 bg-green-100 rounded-full mr-2"></span>
                    <span class="text-sm text-gray-600">A+ à B- (10-20/20)</span>
                </div>
                <div class="flex items-center">
                    <span class="w-4 h-4 bg-yellow-100 rounded-full mr-2"></span>
                    <span class="text-sm text-gray-600">C+ à D+ (8-9.99/20)</span>
                </div>
                <div class="flex items-center">
                    <span class="w-4 h-4 bg-red-100 rounded-full mr-2"></span>
                    <span class="text-sm text-gray-600">D- à F (0-7.99/20)</span>
                </div>
                <div class="flex items-center">
                    <span class="w-4 h-4 bg-gray-100 rounded-full mr-2"></span>
                    <span class="text-sm text-gray-600">N/A (Note non attribuée)</span>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Animation des lignes du tableau au survol
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseenter', () => {
                row.classList.add('bg-gray-50');
            });
            row.addEventListener('mouseleave', () => {
                row.classList.remove('bg-gray-50');
            });
        });
    });
    </script>
</body>
</html>

