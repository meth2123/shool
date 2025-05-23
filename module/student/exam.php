<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Initialiser le contenu pour le template
ob_start();

// Récupérer l'ID de l'étudiant connecté
$student_id = $_SESSION['login_id'];

// Récupérer la classe de l'étudiant
$student_class = db_fetch_row(
    "SELECT classid FROM students WHERE id = ?",
    [$student_id],
    's'
);

if (!$student_class) {
    die("Erreur: Impossible de récupérer la classe de l'étudiant.");
}

// Récupérer les cours de l'étudiant avec leurs examens et devoirs
$courses = db_fetch_all(
    "SELECT DISTINCT 
        c.id as course_id,
        c.name as course_name,
        c.coefficient as course_coefficient,
        e.id as exam_id,
        e.examdate,
        e.time,
        CASE 
            WHEN u.usertype = 'admin' THEN 'examen'
            ELSE 'devoir'
        END as exam_type,
        DATE_FORMAT(e.examdate, '%d/%m/%Y') as formatted_date,
        TIME_FORMAT(e.time, '%H:%i') as formatted_time,
        CASE 
            WHEN e.examdate < CURDATE() THEN 'past'
            WHEN e.examdate = CURDATE() THEN 'today'
            ELSE 'upcoming'
        END as status
    FROM course c
    LEFT JOIN examschedule e ON c.id = e.courseid
    LEFT JOIN users u ON e.created_by = u.userid
    WHERE c.classid = ?
    AND c.id IN (
        SELECT course_id 
        FROM student_teacher_course 
        WHERE student_id = ?
    )
    ORDER BY e.examdate ASC, e.time ASC",
    [$student_class['classid'], $student_id],
    'ss'
);

// Organiser les examens et devoirs par cours
$organized_exams = [];
foreach ($courses as $course) {
    if (!isset($organized_exams[$course['course_id']])) {
        $organized_exams[$course['course_id']] = [
            'course_name' => $course['course_name'],
            'course_coefficient' => $course['course_coefficient'],
            'exams' => [],
            'assignments' => []
        ];
    }
    if ($course['exam_id']) {
        $exam_data = [
            'id' => $course['exam_id'],
            'date' => $course['examdate'],
            'formatted_date' => $course['formatted_date'],
            'time' => $course['time'],
            'formatted_time' => $course['formatted_time'],
            'status' => $course['status']
        ];
        
        if ($course['exam_type'] === 'examen') {
            $organized_exams[$course['course_id']]['exams'][] = $exam_data;
        } else {
            $organized_exams[$course['course_id']]['assignments'][] = $exam_data;
        }
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <!-- En-tête -->
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800 mb-4">Planning des Évaluations</h2>
        <p class="text-gray-600">Consultez le planning de vos examens et devoirs par cours</p>
    </div>

    <!-- Filtres -->
    <div class="mb-6">
        <div class="flex flex-wrap gap-4">
            <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" onclick="filterEvaluations('all')">
                Toutes les évaluations
            </button>
            <button class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2" onclick="filterEvaluations('upcoming')">
                À venir
            </button>
            <button class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2" onclick="filterEvaluations('today')">
                Aujourd'hui
            </button>
            <button class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2" onclick="filterEvaluations('past')">
                Passés
            </button>
        </div>
    </div>

    <!-- Liste des cours et évaluations -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($organized_exams as $course_id => $course): ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800 mb-1">
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </h3>
                            <p class="text-sm text-gray-600">
                                Coefficient: <?php echo number_format($course['course_coefficient'], 2); ?>
                            </p>
				</div>
						</div>
						 
                    <!-- Examens -->
                    <?php if (!empty($course['exams'])): ?>
                        <div class="mb-6">
                            <h4 class="text-lg font-medium text-gray-700 mb-3">
                                <i class="fas fa-file-alt mr-2"></i>Examens
                            </h4>
                            <div class="space-y-4">
                                <?php foreach ($course['exams'] as $exam): 
                                    $status_config = [
                                        'upcoming' => [
                                            'class' => 'bg-blue-100 text-blue-800',
                                            'icon' => 'fa-calendar-plus',
                                            'text' => 'À venir'
                                        ],
                                        'today' => [
                                            'class' => 'bg-yellow-100 text-yellow-800',
                                            'icon' => 'fa-calendar-day',
                                            'text' => 'Aujourd\'hui'
                                        ],
                                        'past' => [
                                            'class' => 'bg-gray-100 text-gray-600',
                                            'icon' => 'fa-calendar-check',
                                            'text' => 'Passé'
                                        ]
                                    ][$exam['status']];
                                ?>
                                    <div class="evaluation-card border rounded-lg p-4 <?php echo $exam['status']; ?> exam">
                                        <div class="flex justify-between items-start mb-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_config['class']; ?>">
                                                <i class="fas <?php echo $status_config['icon']; ?> mr-1"></i>
                                                <?php echo $status_config['text']; ?>
                                            </span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                <i class="fas fa-file-alt mr-1"></i>
                                                Examen
                                            </span>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="flex items-center text-sm text-gray-600">
                                                <i class="fas fa-calendar mr-2"></i>
                                                <?php echo $exam['formatted_date']; ?>
                                            </div>
                                            <div class="flex items-center text-sm text-gray-600">
                                                <i class="fas fa-clock mr-2"></i>
                                                <?php echo $exam['formatted_time']; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Devoirs -->
                    <?php if (!empty($course['assignments'])): ?>
                        <div>
                            <h4 class="text-lg font-medium text-gray-700 mb-3">
                                <i class="fas fa-tasks mr-2"></i>Devoirs
                            </h4>
                            <div class="space-y-4">
                                <?php foreach ($course['assignments'] as $assignment): 
                                    $status_config = [
                                        'upcoming' => [
                                            'class' => 'bg-blue-100 text-blue-800',
                                            'icon' => 'fa-calendar-plus',
                                            'text' => 'À venir'
                                        ],
                                        'today' => [
                                            'class' => 'bg-yellow-100 text-yellow-800',
                                            'icon' => 'fa-calendar-day',
                                            'text' => 'Aujourd\'hui'
                                        ],
                                        'past' => [
                                            'class' => 'bg-gray-100 text-gray-600',
                                            'icon' => 'fa-calendar-check',
                                            'text' => 'Passé'
                                        ]
                                    ][$assignment['status']];
                                ?>
                                    <div class="evaluation-card border rounded-lg p-4 <?php echo $assignment['status']; ?> assignment">
                                        <div class="flex justify-between items-start mb-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_config['class']; ?>">
                                                <i class="fas <?php echo $status_config['icon']; ?> mr-1"></i>
                                                <?php echo $status_config['text']; ?>
                                            </span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-tasks mr-1"></i>
                                                Devoir
                                            </span>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="flex items-center text-sm text-gray-600">
                                                <i class="fas fa-calendar mr-2"></i>
                                                <?php echo $assignment['formatted_date']; ?>
                                            </div>
                                            <div class="flex items-center text-sm text-gray-600">
                                                <i class="fas fa-clock mr-2"></i>
                                                <?php echo $assignment['formatted_time']; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($course['exams']) && empty($course['assignments'])): ?>
                        <div class="text-center py-4 text-gray-500">
                            Aucune évaluation programmée
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function filterEvaluations(status) {
    const evaluationCards = document.querySelectorAll('.evaluation-card');
    evaluationCards.forEach(card => {
        if (status === 'all' || card.classList.contains(status)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Ajouter des filtres pour les types d'évaluation
document.addEventListener('DOMContentLoaded', function() {
    const filterContainer = document.querySelector('.mb-6');
    const typeFilters = document.createElement('div');
    typeFilters.className = 'flex flex-wrap gap-4 mt-4';
    typeFilters.innerHTML = `
        <button class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2" onclick="filterByType('all')">
            Tous les types
        </button>
        <button class="px-4 py-2 bg-purple-500 text-white rounded-md hover:bg-purple-600 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:ring-offset-2" onclick="filterByType('exam')">
            Examens uniquement
        </button>
        <button class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-offset-2" onclick="filterByType('assignment')">
            Devoirs uniquement
        </button>
    `;
    filterContainer.appendChild(typeFilters);
});

function filterByType(type) {
    const evaluationCards = document.querySelectorAll('.evaluation-card');
    evaluationCards.forEach(card => {
        if (type === 'all' || card.classList.contains(type)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

<?php
$content = ob_get_clean();
include('templates/layout.php');
?>

