<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Récupération des informations du parent
$parent_info = db_fetch_row(
    "SELECT * FROM parents WHERE id = ?",
    [$check],
    's'
);

if (!$parent_info) {
    header("Location: ../../?error=parent_not_found");
    exit();
}

// Récupération des enfants du parent
$children = db_fetch_all(
    "SELECT s.*, c.name as class_name, c.section 
     FROM students s
     LEFT JOIN class c ON s.classid = c.id
     WHERE s.parentid = ?",
    [$check],
    's'
) ?: [];

// Fonction pour convertir les notes en valeurs numériques (plus nécessaire car les notes sont déjà numériques)
function convertGradeToNumeric($grade) {
    return floatval($grade);
}

// Fonction pour obtenir la moyenne d'un élève
function getStudentAverage($studentId) {
    // Requête pour obtenir toutes les notes de l'élève
    $grades = db_fetch_all(
        "SELECT stc.*, c.name as course_name, t.name as teacher_name,
                CASE 
                    WHEN stc.grade_type = 'examen' THEN 'Examen'
                    WHEN stc.grade_type = 'devoir' THEN 'Devoir ' || stc.grade_number
                    ELSE 'Autre'
                END as grade_type_display
         FROM student_teacher_course stc
         INNER JOIN course c ON stc.course_id = c.id 
         INNER JOIN teachers t ON stc.teacher_id = t.id 
         WHERE stc.student_id = ? 
         AND stc.grade IS NOT NULL
         ORDER BY stc.semester, stc.course_id, stc.grade_type, stc.grade_number",
        [$studentId],
        's'
    ) ?: [];

    if (empty($grades)) {
        return ['average' => 0, 'grades' => []];
    }

    // Calcul des moyennes par matière et par semestre
    $courseAverages = [];
    $semesterAverages = [];
    $totalWeightedSum = 0;
    $totalWeight = 0;

    foreach ($grades as $grade) {
        $courseId = $grade['course_id'];
        $semester = $grade['semester'];
        $gradeValue = floatval($grade['grade']);
        $coefficient = floatval($grade['coefficient']);

        // Initialisation des tableaux si nécessaire
        if (!isset($courseAverages[$courseId])) {
            $courseAverages[$courseId] = [
                'name' => $grade['course_name'],
                'teacher' => $grade['teacher_name'],
                'semesters' => [],
                'total_weighted_sum' => 0,
                'total_weight' => 0
            ];
        }
        if (!isset($courseAverages[$courseId]['semesters'][$semester])) {
            $courseAverages[$courseId]['semesters'][$semester] = [
                'weighted_sum' => 0,
                'weight' => 0,
                'grades' => []
            ];
        }
        if (!isset($semesterAverages[$semester])) {
            $semesterAverages[$semester] = [
                'weighted_sum' => 0,
                'weight' => 0
            ];
        }

        // Ajout de la note avec son coefficient
        $courseAverages[$courseId]['semesters'][$semester]['grades'][] = [
            'type' => $grade['grade_type_display'],
            'grade' => $gradeValue,
            'coefficient' => $coefficient
        ];
        $courseAverages[$courseId]['semesters'][$semester]['weighted_sum'] += $gradeValue * $coefficient;
        $courseAverages[$courseId]['semesters'][$semester]['weight'] += $coefficient;
        $courseAverages[$courseId]['total_weighted_sum'] += $gradeValue * $coefficient;
        $courseAverages[$courseId]['total_weight'] += $coefficient;

        // Calcul de la moyenne par semestre
        $semesterAverages[$semester]['weighted_sum'] += $gradeValue * $coefficient;
        $semesterAverages[$semester]['weight'] += $coefficient;

        // Calcul de la moyenne générale
        $totalWeightedSum += $gradeValue * $coefficient;
        $totalWeight += $coefficient;
    }

    // Calcul des moyennes finales
    $generalAverage = $totalWeight > 0 ? round($totalWeightedSum / $totalWeight, 2) : 0;

    // Calcul des moyennes par matière et par semestre
    foreach ($courseAverages as $courseId => &$course) {
        foreach ($course['semesters'] as $semester => &$semesterData) {
            $semesterData['average'] = $semesterData['weight'] > 0 
                ? round($semesterData['weighted_sum'] / $semesterData['weight'], 2) 
                : 0;
        }
        $course['average'] = $course['total_weight'] > 0 
            ? round($course['total_weighted_sum'] / $course['total_weight'], 2) 
            : 0;
    }

    // Calcul des moyennes par semestre
    foreach ($semesterAverages as $semester => &$semesterData) {
        $semesterData['average'] = $semesterData['weight'] > 0 
            ? round($semesterData['weighted_sum'] / $semesterData['weight'], 2) 
            : 0;
    }

    return [
        'average' => $generalAverage,
        'grades' => $grades,
        'courseAverages' => $courseAverages,
        'semesterAverages' => $semesterAverages
    ];
}

// Fonction pour obtenir les commentaires des enseignants
function getTeacherReports($studentId) {
    return db_fetch_all(
        "SELECT r.*, t.name as teacher_name, c.name as course_name 
         FROM report r
         LEFT JOIN teachers t ON r.teacherid = t.id 
         LEFT JOIN course c ON r.courseid = c.id 
         WHERE r.studentid = ? 
         ORDER BY r.reportid DESC",
        [$studentId],
        's'
    ) ?: [];
}

// Couleurs pour les notes
$gradeColors = [
    'A+' => 'bg-green-100 text-green-800',
    'A' => 'bg-green-100 text-green-800',
    'A-' => 'bg-green-100 text-green-800',
    'B+' => 'bg-blue-100 text-blue-800',
    'B' => 'bg-blue-100 text-blue-800',
    'B-' => 'bg-blue-100 text-blue-800',
    'C+' => 'bg-yellow-100 text-yellow-800',
    'C' => 'bg-yellow-100 text-yellow-800',
    'C-' => 'bg-yellow-100 text-yellow-800',
    'D+' => 'bg-orange-100 text-orange-800',
    'D' => 'bg-orange-100 text-orange-800',
    'D-' => 'bg-orange-100 text-orange-800',
    'F' => 'bg-red-100 text-red-800'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletins - Système de Gestion Scolaire</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        function showTab(tabId) {
            // Cacher tous les contenus
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            // Désactiver tous les onglets
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('bg-blue-600', 'text-white');
                button.classList.add('bg-gray-100', 'text-gray-700');
            });
            // Afficher le contenu sélectionné
            document.getElementById(tabId).classList.remove('hidden');
            // Activer l'onglet sélectionné
            document.querySelector(`[onclick="showTab('${tabId}')"]`).classList.remove('bg-gray-100', 'text-gray-700');
            document.querySelector(`[onclick="showTab('${tabId}')"]`).classList.add('bg-blue-600', 'text-white');
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <img src="../../source/logo.jpg" class="h-12 w-12 object-contain" alt="School Management System"/>
                    <h1 class="ml-4 text-xl font-semibold text-gray-800">Système de Gestion Scolaire</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Bonjour, <?php echo htmlspecialchars($parent_info['fathername']); ?></span>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Menu de navigation -->
    <div class="bg-white shadow-md mt-6 mx-4 lg:mx-auto max-w-7xl rounded-lg">
        <div class="flex flex-wrap justify-center gap-4 p-4">
            <a href="index.php" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-home mr-2"></i>Accueil
            </a>
            <a href="modify.php" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-key mr-2"></i>Changer le mot de passe
            </a>
            <a href="checkchild.php" class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-child mr-2"></i>Information enfant
            </a>
            <a href="childpayment.php" class="bg-indigo-500 hover:bg-indigo-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-money-bill-wave mr-2"></i>Paiements
            </a>
            <a href="childattendance.php" class="bg-pink-500 hover:bg-pink-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-calendar-check mr-2"></i>Présences
            </a>
            <a href="childreport.php" class="bg-teal-500 hover:bg-teal-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-file-alt mr-2"></i>Bulletins
            </a>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <?php if (empty($children)): ?>
            <div class="text-center py-8">
                <i class="fas fa-info-circle text-4xl text-blue-500 mb-4"></i>
                <h2 class="text-2xl font-semibold text-gray-700 mb-2">Aucun enfant trouvé</h2>
                <p class="text-gray-600">Vous n'avez pas encore d'enfants inscrits dans le système.</p>
            </div>
        <?php else: ?>
            <!-- Onglets pour chaque enfant -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <?php foreach ($children as $index => $child): ?>
                            <button onclick="showTab('tab-<?php echo $child['id']; ?>')" 
                                    class="tab-button px-6 py-3 text-sm font-medium <?php echo $index === 0 ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'; ?> hover:bg-blue-50 hover:text-blue-600 transition-colors duration-150 ease-in-out">
                                <?php echo htmlspecialchars($child['name']); ?>
                            </button>
                        <?php endforeach; ?>
                    </nav>
                </div>

                <!-- Contenu des onglets -->
                <?php foreach ($children as $index => $child): 
                    $studentData = getStudentAverage($child['id']);
                    $reports = getTeacherReports($child['id']);
                ?>
                    <div id="tab-<?php echo $child['id']; ?>" class="tab-content p-6 <?php echo $index === 0 ? '' : 'hidden'; ?>">
                        <!-- En-tête du bulletin -->
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white mb-6">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($child['name']); ?></h2>
                                    <p class="text-blue-100 mt-1">
                                        <?php echo htmlspecialchars($child['class_name'] . ' ' . $child['section']); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div class="text-3xl font-bold">
                                        <?php echo number_format($studentData['average'], 2); ?>/20
                                    </div>
                                    <div class="text-blue-100">Moyenne générale</div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes par matière -->
                        <div class="bg-white rounded-lg shadow-sm mb-6">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Notes par matière</h3>
                            </div>
            <div class="p-6">
                                <?php if (empty($studentData['courseAverages'])): ?>
                                    <p class="text-center text-gray-500">Aucune note disponible</p>
                                <?php else: ?>
                                    <?php foreach ($studentData['courseAverages'] as $courseId => $course): ?>
                                        <div class="mb-8 last:mb-0">
                                            <div class="flex justify-between items-center mb-4">
                    <div>
                                                    <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($course['name']); ?></h4>
                                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($course['teacher']); ?></p>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-xl font-bold text-blue-600">
                                                        <?php echo number_format($course['average'], 2); ?>/20
                                                    </div>
                                                    <div class="text-sm text-gray-600">Moyenne de la matière</div>
                                                </div>
                                            </div>
                                            
                                            <?php foreach ($course['semesters'] as $semester => $semesterData): ?>
                                                <div class="mb-4 last:mb-0">
                                                    <h5 class="text-md font-medium text-gray-700 mb-2">Semestre <?php echo $semester; ?></h5>
                                                    <div class="bg-gray-50 rounded-lg p-4">
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                            <?php foreach ($semesterData['grades'] as $grade): ?>
                                                                <div class="flex justify-between items-center">
                                                                    <span class="text-gray-600"><?php echo htmlspecialchars($grade['type']); ?></span>
                                                                    <div class="flex items-center space-x-2">
                                                                        <span class="font-medium"><?php echo number_format($grade['grade'], 2); ?>/20</span>
                                                                        <span class="text-sm text-gray-500">(coef. <?php echo $grade['coefficient']; ?>)</span>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <div class="mt-3 pt-3 border-t border-gray-200 flex justify-between items-center">
                                                            <span class="text-gray-600">Moyenne du semestre</span>
                                                            <span class="font-bold text-blue-600"><?php echo number_format($semesterData['average'], 2); ?>/20</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                            <?php endforeach; ?>
                                    
                                    <!-- Moyennes par semestre -->
                                    <div class="mt-8 pt-6 border-t border-gray-200">
                                        <h4 class="text-lg font-medium text-gray-900 mb-4">Moyennes par semestre</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <?php foreach ($studentData['semesterAverages'] as $semester => $semesterData): ?>
                                                <div class="bg-gray-50 rounded-lg p-4">
                                                    <div class="flex justify-between items-center">
                                                        <span class="text-gray-600">Semestre <?php echo $semester; ?></span>
                                                        <span class="text-xl font-bold text-blue-600"><?php echo number_format($semesterData['average'], 2); ?>/20</span>
                                                    </div>
                    </div>
                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                    </div>
                </div>

                        <!-- Commentaires des enseignants -->
                        <div class="bg-white rounded-lg shadow-sm">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Commentaires des enseignants</h3>
                            </div>
                            <div class="p-6">
                                <?php if (empty($reports)): ?>
                                    <p class="text-center text-gray-500">Aucun commentaire disponible</p>
                                <?php else: ?>
                                    <div class="space-y-4">
                                        <?php foreach ($reports as $report): ?>
                                            <div class="bg-gray-50 rounded-lg p-4">
                                                <div class="flex justify-between items-start mb-2">
                                                    <div>
                                                        <h4 class="font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($report['teacher_name']); ?>
                                                        </h4>
                                                        <p class="text-sm text-gray-600">
                                                            <?php echo htmlspecialchars($report['course_name']); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                <p class="text-gray-700">
                                                    <?php echo nl2br(htmlspecialchars($report['message'])); ?>
                                                </p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-white shadow-lg mt-8">
        <div class="max-w-7xl mx-auto py-4 px-4">
            <p class="text-center text-gray-500 text-sm">
                © <?php echo date('Y'); ?> Système de Gestion Scolaire. Tous droits réservés.
            </p>
        </div>
    </footer>
</body>
</html>

