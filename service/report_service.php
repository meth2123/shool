<?php
require_once('db_utils.php');
session_start();

// Vérification de la session
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Session non valide'
    ]);
    exit();
}

// Vérification des paramètres requis
if (!isset($_POST['action']) || !isset($_POST['student_id']) || !isset($_POST['period'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Paramètres manquants'
    ]);
    exit();
}

$action = $_POST['action'];
$student_id = $_POST['student_id'];
$trimester = $_POST['period']; // Le trimestre (1, 2 ou 3)

try {
    if ($action === 'get_report') {
        // Récupération des informations de l'élève
        $student = db_fetch_row(
            "SELECT s.*, c.name as class_name 
             FROM students s
             LEFT JOIN class c ON s.classid = c.id
             WHERE s.id = ?",
            [$student_id],
            's'
        );

        if (!$student) {
            throw new Exception('Élève non trouvé');
        }

        // Récupération des notes de l'élève pour le trimestre
        $grades = db_fetch_all(
            "SELECT g.*, c.name as subject, c.coefficient
             FROM grade g
             LEFT JOIN course c ON g.courseid = c.id
             WHERE g.studentid = ? 
             AND g.trimester = ?
             ORDER BY c.name",
            [$student_id, $trimester],
            'ss'
        );

        if (!$grades) {
            $grades = [];
        }

        // Calcul de la moyenne générale pondérée
        $total_weighted = 0;
        $total_coeff = 0;
        foreach ($grades as $grade) {
            $coeff = $grade['coefficient'] ?? 1;
            $total_weighted += ($grade['grade'] * $coeff);
            $total_coeff += $coeff;
        }
        $average = $total_coeff > 0 ? round($total_weighted / $total_coeff, 2) : 0;

        // Récupération du rang de l'élève
        $rank_query = db_fetch_row(
            "SELECT COUNT(*) + 1 as rank
             FROM (
                 SELECT g.studentid, 
                        SUM(g.grade * COALESCE(c.coefficient, 1)) / SUM(COALESCE(c.coefficient, 1)) as avg_grade
                 FROM grade g
                 LEFT JOIN course c ON g.courseid = c.id
                 WHERE g.trimester = ? 
                 AND g.classid = ?
                 GROUP BY g.studentid
                 HAVING SUM(g.grade * COALESCE(c.coefficient, 1)) / SUM(COALESCE(c.coefficient, 1)) > (
                     SELECT SUM(g2.grade * COALESCE(c2.coefficient, 1)) / SUM(COALESCE(c2.coefficient, 1))
                     FROM grade g2
                     LEFT JOIN course c2 ON g2.courseid = c2.id
                     WHERE g2.studentid = ? 
                     AND g2.trimester = ?
                 )
             ) better_students",
            [$trimester, $student['classid'], $student_id, $trimester],
            'siss'
        );

        // Formatage des notes avec commentaires
        $formatted_grades = array_map(function($grade) {
            return [
                'subject' => $grade['subject'],
                'grade' => $grade['grade'],
                'comment' => getGradeComment($grade['grade'])
            ];
        }, $grades);

        // Construction de la réponse
        $response = [
            'success' => true,
            'data' => [
                'student_name' => $student['name'],
                'class_name' => $student['class_name'],
                'period' => getTrimesterName($trimester),
                'average' => $average,
                'rank' => $rank_query['rank'],
                'grades' => $formatted_grades,
                'general_comment' => getGeneralComment($average)
            ]
        ];

        echo json_encode($response);
    } else {
        throw new Exception('Action non valide');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Fonction pour obtenir le nom du trimestre
function getTrimesterName($trimester) {
    switch ($trimester) {
        case '1':
            return 'Premier Trimestre';
        case '2':
            return 'Deuxième Trimestre';
        case '3':
            return 'Troisième Trimestre';
        default:
            return 'Trimestre ' . $trimester;
    }
}

// Fonction pour générer un commentaire en fonction de la note
function getGradeComment($grade) {
    if ($grade >= 16) return "Excellent";
    if ($grade >= 14) return "Très bien";
    if ($grade >= 12) return "Bien";
    if ($grade >= 10) return "Assez bien";
    if ($grade >= 8) return "Insuffisant";
    return "À améliorer";
}

// Fonction pour générer un commentaire général
function getGeneralComment($average) {
    if ($average >= 16) {
        return "Excellent trimestre ! Continue ainsi !";
    } elseif ($average >= 14) {
        return "Très bon trimestre. Maintiens tes efforts !";
    } elseif ($average >= 12) {
        return "Bon trimestre dans l'ensemble. Continue de progresser.";
    } elseif ($average >= 10) {
        return "Trimestre satisfaisant mais des efforts supplémentaires sont nécessaires.";
    } elseif ($average >= 8) {
        return "Trimestre insuffisant. Un travail plus régulier est indispensable.";
    } else {
        return "Trimestre très insuffisant. Un changement d'attitude face au travail est nécessaire.";
    }
} 