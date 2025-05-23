<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification si l'étudiant existe
$student_exists = db_fetch_row(
    "SELECT id FROM students WHERE id = ?",
    [$check],
    's'
);

if (!$student_exists) {
    header("Location: ../../?error=student_not_found");
    exit();
}

// Vérification si l'étudiant a une classe assignée
$student_info = db_fetch_row(
    "SELECT s.*, c.name as class_name 
     FROM students s 
     JOIN class c ON s.classid = c.id 
     WHERE s.id = ?",
    [$check],
    's'
);

if (!$student_info) {
    // Vérification explicite si c'est un problème de classe
    $student_without_class = db_fetch_row(
        "SELECT id, name FROM students WHERE id = ? AND (classid IS NULL OR classid = '')",
        [$check],
        's'
    );
    
    if ($student_without_class) {
        header("Location: ../../?error=student_no_class&student_name=" . urlencode($student_without_class['name']));
    } else {
        header("Location: ../../?error=student_class_not_found");
    }
    exit();
}

// Récupération des notes de l'étudiant
$grades_query = "
    SELECT DISTINCT
        c.name as course_name,
        c.coefficient as course_coefficient,
        stc.grade_type,
        stc.grade_number,
        stc.grade,
        stc.semester,
        t.name as teacher_name,
        DATE_FORMAT(stc.created_at, '%d/%m/%Y') as grade_date
    FROM student_teacher_course stc
    JOIN course c ON stc.course_id = c.id
    JOIN teachers t ON stc.teacher_id = t.id
    WHERE stc.student_id = ?
    AND stc.class_id = ?
    AND stc.grade IS NOT NULL
    ORDER BY c.name, stc.semester, stc.grade_type, stc.grade_number, stc.created_at DESC";

$grades = db_fetch_all($grades_query, [$check, $student_info['classid']], 'ss');

// Calcul des moyennes par matière
$subjects = [];
$totalWeightedSum = 0;  // Somme des (moyenne * coefficient) pour chaque matière
$totalCoefficients = 0;  // Somme des coefficients des matières

foreach ($grades as $grade) {
    if (!isset($subjects[$grade['course_name']])) {
        $subjects[$grade['course_name']] = [
            'total' => 0,           // Somme des notes
            'count' => 0,           // Nombre de notes
            'course_coefficient' => floatval($grade['course_coefficient']),  // Coefficient de la matière
            'grades' => []          // Liste des notes
        ];
    }
    $subjects[$grade['course_name']]['grades'][] = $grade;
    $grade_value = floatval($grade['grade']);
    $subjects[$grade['course_name']]['total'] += $grade_value;
    $subjects[$grade['course_name']]['count']++;
}

// Calcul de la moyenne générale
foreach ($subjects as $subject) {
    if ($subject['count'] > 0) {
        // Calcul de la moyenne de la matière
        $subject_average = $subject['total'] / $subject['count'];
        
        // Ajout de la contribution pondérée à la moyenne générale
        $totalWeightedSum += $subject_average * $subject['course_coefficient'];
        $totalCoefficients += $subject['course_coefficient'];
    }
}

// Calcul de la moyenne générale pondérée
$generalAverage = $totalCoefficients > 0 ? $totalWeightedSum / $totalCoefficients : 0;

function getAppreciation($note) {
    if ($note === null) return "Non évalué";
    if ($note >= 16) return "Excellent";
    if ($note >= 14) return "Très bien";
    if ($note >= 12) return "Bien";
    if ($note >= 10) return "Assez bien";
    if ($note >= 8) return "Passable";
    return "Insuffisant";
}
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin - Système de Gestion Scolaire</title>
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
                    <h1 class="ml-4 text-xl font-semibold text-gray-800">Système de Gestion Scolaire</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Bonjour, <?php echo htmlspecialchars($student_info['name']); ?></span>
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
                <i class="fas fa-key mr-2"></i>Changer mot de passe
            </a>
            <a href="course.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-graduation-cap mr-2"></i>Mes cours et résultats
            </a>
            <a href="exam.php" class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-calendar-alt mr-2"></i>Planning des examens
            </a>
            <a href="attendance.php" class="bg-pink-500 hover:bg-pink-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-calendar-check mr-2"></i>Mes présences
            </a>
        </div>
				</div>

    <!-- Bulletin de notes -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Bulletin de notes</h2>
                <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                    <i class="fas fa-print mr-2"></i>Imprimer
                </button>
						</div>
						 
            <!-- En-tête du bulletin -->
            <div class="mb-8 text-center">
                <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($student_info['name']); ?></h3>
                <p class="text-gray-600">Classe : <?php echo htmlspecialchars($student_info['class_name']); ?></p>
                <p class="text-gray-600">Année scolaire : <?php echo date('Y'); ?>-<?php echo date('Y')+1; ?></p>
            </div>

            <!-- Notes par matière -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matière</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Note</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Professeur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Moyenne</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        $current_subject = '';
                        $subject_grades = [];
                        $subject_total = 0;
                        $subject_count = 0;
                        
                        foreach ($grades as $grade): 
                            if ($current_subject !== $grade['course_name']) {
                                // Afficher la moyenne du sujet précédent si nécessaire
                                if ($current_subject !== '') {
                                    $average = $subject_count > 0 ? $subject_total / $subject_count : 0;
                                    echo '<tr class="bg-gray-50">';
                                    echo '<td colspan="5" class="px-6 py-4 text-right font-medium text-gray-900">Moyenne ' . htmlspecialchars($current_subject) . ' :</td>';
                                    echo '<td class="px-6 py-4 text-left font-medium text-gray-900">';
                                    echo number_format($average, 2) . '/20';
                                    echo '<div class="text-xs text-gray-500">' . getAppreciation($average) . '</div>';
                                    echo '</td></tr>';
                                }
                                
                                // Réinitialiser les variables pour le nouveau sujet
                                $current_subject = $grade['course_name'];
                                $subject_grades = [];
                                $subject_total = 0;
                                $subject_count = 0;
                            }
                            
                            // Ajouter la note au calcul de la moyenne
                            $grade_value = floatval($grade['grade']);
                            $subject_total += $grade_value;
                            $subject_count++;
                        ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($grade['course_name']); ?>
                                    <div class="text-xs text-gray-500">(coef <?php echo number_format($grade['course_coefficient'], 2); ?>)</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php 
                                    echo $grade['grade_type'] === 'devoir' ? 'Devoir ' : 'Examen ';
                                    echo htmlspecialchars($grade['grade_number'] ?? '');
                                    echo ' (S' . htmlspecialchars($grade['semester']) . ')';
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo number_format($grade['grade'], 2); ?>/20
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($grade['teacher_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($grade['grade_date']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php 
                                    $average = $subject_count > 0 ? $subject_total / $subject_count : 0;
                                    echo number_format($average, 2) . '/20';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; 
                        
                        // Afficher la moyenne du dernier sujet
                        if ($current_subject !== '') {
                            $average = $subject_count > 0 ? $subject_total / $subject_count : 0;
                            echo '<tr class="bg-gray-50">';
                            echo '<td colspan="5" class="px-6 py-4 text-right font-medium text-gray-900">Moyenne ' . htmlspecialchars($current_subject) . ' :</td>';
                            echo '<td class="px-6 py-4 text-left font-medium text-gray-900">';
                            echo number_format($average, 2) . '/20';
                            echo '<div class="text-xs text-gray-500">' . getAppreciation($average) . '</div>';
                            echo '</td></tr>';
                        }
                        ?>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-right font-medium text-gray-900">
                                Moyenne générale (pondérée) :
                                <div class="text-xs text-gray-500">
                                    <?php
                                    // Affichage du détail du calcul
                                    $details = [];
                                    foreach ($subjects as $name => $subject) {
                                        if ($subject['count'] > 0) {
                                            $average = $subject['total'] / $subject['count'];
                                            $details[] = sprintf(
                                                "%s: %.2f × %.2f = %.2f",
                                                $name,
                                                $average,
                                                $subject['course_coefficient'],
                                                $average * $subject['course_coefficient']
                                            );
                                        }
                                    }
                                    echo "(" . implode(" + ", $details) . ") ÷ " . number_format($totalCoefficients, 2) . " = " . number_format($generalAverage, 2);
                                    ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-left font-medium text-gray-900">
                                <?php echo number_format($generalAverage, 2); ?>/20
                                <div class="text-xs text-gray-500"><?php echo getAppreciation($generalAverage); ?></div>
                            </td>
                        </tr>
                    </tfoot>
</table>
            </div>
        </div>
			</div>					
							
    <!-- Footer -->
    <footer class="bg-white shadow-lg mt-8">
        <div class="max-w-7xl mx-auto py-4 px-4">
            <p class="text-center text-gray-500 text-sm">
                © <?php echo date('Y'); ?> Système de Gestion Scolaire. Tous droits réservés.
            </p>
        </div>
    </footer>

    <style>
    @media print {
        body * {
            visibility: hidden;
        }
        .bg-white.rounded-lg.shadow-lg, .bg-white.rounded-lg.shadow-lg * {
            visibility: visible;
        }
        .bg-white.rounded-lg.shadow-lg {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        nav, .menu, footer, button {
            display: none !important;
        }
    }
    </style>
		</body>
</html>

