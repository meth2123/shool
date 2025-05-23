<?php 
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification de la session
if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$teacher_id = $_SESSION['login_id'];

// Récupérer les classes du professeur
$classes_query = "SELECT DISTINCT cl.id, cl.name 
                 FROM class cl 
                 JOIN course c ON cl.id = c.classid 
                 WHERE c.teacherid = ?";
$classes = db_fetch_all($classes_query, [$teacher_id], 's');

// Récupérer les paramètres de filtrage
$selected_class = $_GET['class_id'] ?? '';
$sort_by = $_GET['sort'] ?? 'date';
$filter_status = $_GET['status'] ?? 'all';

// Requête pour les devoirs (créés par le professeur)
$assignments_query = "SELECT DISTINCT 
    e.id,
    c.name as course_name,
    c.id as course_id,
    e.examdate,
    e.time,
    cl.name as class_name,
    cl.id as class_id,
    e.title,
    e.description,
    e.created_by,
    CASE 
        WHEN e.examdate < CURDATE() THEN 'past'
        WHEN e.examdate = CURDATE() THEN 'today'
        ELSE 'upcoming'
    END as status,
    DATE_FORMAT(e.examdate, '%d/%m/%Y') as formatted_date,
    TIME_FORMAT(e.time, '%H:%i') as formatted_time
FROM course c
JOIN examschedule e ON c.id = e.courseid
JOIN class cl ON c.classid = cl.id
WHERE c.teacherid = ? AND e.created_by = ?";

// Requête pour les examens (créés par l'admin)
$exams_query = "SELECT DISTINCT 
    e.id,
    c.name as course_name,
    c.id as course_id,
    e.examdate,
    e.time,
    cl.name as class_name,
    cl.id as class_id,
    e.title,
    e.description,
    e.created_by,
    CASE 
        WHEN e.examdate < CURDATE() THEN 'past'
        WHEN e.examdate = CURDATE() THEN 'today'
        ELSE 'upcoming'
    END as status,
    DATE_FORMAT(e.examdate, '%d/%m/%Y') as formatted_date,
    TIME_FORMAT(e.time, '%H:%i') as formatted_time
FROM course c
JOIN examschedule e ON c.id = e.courseid
JOIN class cl ON c.classid = cl.id
WHERE c.teacherid = ? 
AND e.created_by IN ('admin', 'ad-123-1')";

// Log des requêtes et paramètres
error_log("Requête devoirs: " . $assignments_query);
error_log("Paramètres devoirs: " . print_r([$teacher_id, $teacher_id], true));

error_log("Requête examens: " . $exams_query);
error_log("Paramètres examens: " . print_r([$teacher_id], true));

// Exécuter les requêtes
$assignments = db_fetch_all($assignments_query, [$teacher_id, $teacher_id], 'ss');
$exams = db_fetch_all($exams_query, [$teacher_id], 's');

// Log des résultats
error_log("Nombre de devoirs trouvés: " . ($assignments ? count($assignments) : 0));
error_log("Nombre d'examens trouvés: " . ($exams ? count($exams) : 0));
error_log("Détail des examens trouvés: " . print_r($exams, true));

if ($assignments === false || $exams === false) {
    error_log("Erreur lors de la récupération des données");
    die("Erreur lors de la récupération des données");
}

// Compter les examens par statut
$stats = [
    'all' => count($exams),
    'upcoming' => 0,
    'today' => 0,
    'past' => 0
];

foreach ($exams as $exam) {
    $stats[$exam['status']]++;
}

// Récupérer les cours de la classe sélectionnée
$courses = [];
if ($selected_class) {
    $courses = db_fetch_all(
        "SELECT id, name FROM course WHERE classid = ? AND teacherid = ?",
        [$selected_class, $teacher_id],
        'ss'
    );
}

// Traitement du formulaire d'ajout de devoir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_assignment'])) {
    error_log("=== TENTATIVE D'AJOUT DE DEVOIR ===");
    error_log("POST data: " . print_r($_POST, true));
    
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $due_time = $_POST['due_time'];
    
    error_log("Données extraites - Course: $course_id, Title: $title, Date: $due_date, Time: $due_time");
    error_log("Teacher ID: $teacher_id");
    
    // Vérifier que le cours appartient bien au professeur
    $check_course_query = "SELECT id, teacherid, classid FROM course WHERE id = ? AND teacherid = ?";
    error_log("Requête de vérification: " . $check_course_query);
    
    $check_course = db_fetch_row($check_course_query, [$course_id, $teacher_id], 'ss');
    error_log("Résultat de la vérification: " . print_r($check_course, true));
    
    if ($check_course) {
        // Générer un ID unique pour le devoir
        $assignment_id = 'ASS-' . uniqid();
        error_log("ID généré pour le devoir: $assignment_id");
        
        $insert_query = "INSERT INTO examschedule (id, courseid, title, description, examdate, time, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        error_log("Requête d'insertion: " . $insert_query);
        
        $params = [
            $assignment_id,
            $course_id,
            $title,
            $description,
            $due_date,
            $due_time,
            $teacher_id
        ];
        error_log("Paramètres d'insertion: " . print_r($params, true));
        
        $result = db_query($insert_query, $params, 'sssssss');
        error_log("Résultat de l'insertion: " . ($result ? "succès" : "échec"));
        
        if (!$result) {
            error_log("Erreur SQL: " . db_error());
        }
        
        if ($result) {
            error_log("Redirection vers la page avec succès");
            header("Location: exam.php?class_id=" . $selected_class . "&success=1");
        } else {
            error_log("Redirection vers la page avec erreur");
            header("Location: exam.php?class_id=" . $selected_class . "&error=insert_failed");
        }
        exit();
    } else {
        error_log("Erreur: Le cours n'appartient pas au professeur");
        header("Location: exam.php?class_id=" . $selected_class . "&error=unauthorized");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Examens - Système de Gestion Scolaire</title>
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
                    <h1 class="ml-4 text-xl font-semibold text-gray-800">Gestion des Examens</h1>
                </div>
                <div class="flex items-center space-x-4">
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

    <!-- Contenu principal -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Sélection de la classe -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Sélectionner une classe</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($classes as $class): ?>
                    <a href="?class_id=<?php echo htmlspecialchars($class['id']); ?>" 
                       class="block p-4 border rounded-lg hover:bg-gray-50 transition duration-150 ease-in-out <?php echo $selected_class === $class['id'] ? 'border-blue-500 bg-blue-50' : 'border-gray-200'; ?>">
                        <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($class['name']); ?></h3>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($class['description'] ?? ''); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Après la section de sélection de classe -->
        <?php if ($selected_class): ?>
            <!-- Formulaire d'ajout de devoir -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Ajouter un devoir</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="add_assignment" value="1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="course_id" class="block text-sm font-medium text-gray-700">Cours</label>
                            <select name="course_id" id="course_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Sélectionner un cours</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo htmlspecialchars($course['id']); ?>">
                                        <?php echo htmlspecialchars($course['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">Titre du devoir</label>
                            <input type="text" name="title" id="title" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="Ex: Devoir sur les fonctions">
                        </div>
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="description" rows="3" required
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="Description détaillée du devoir..."></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="due_date" class="block text-sm font-medium text-gray-700">Date limite</label>
                            <input type="date" name="due_date" id="due_date" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div>
                            <label for="due_time" class="block text-sm font-medium text-gray-700">Heure limite</label>
                            <input type="time" name="due_time" id="due_time" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-150 ease-in-out">
                            <i class="fas fa-plus mr-2"></i>
                            Ajouter le devoir
                        </button>
                    </div>
                </form>
            </div>

            <!-- Section des devoirs -->
            <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Mes devoirs</h2>
                    <?php if (!empty($assignments)): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($assignments as $assignment): 
                                $status_config = [
                                    'upcoming' => [
                                        'class' => 'bg-green-100 text-green-800',
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
                                <div class="border rounded-lg p-4 hover:shadow-md transition duration-150 ease-in-out">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h3 class="font-medium text-gray-900 mb-1">
                                                <?php echo htmlspecialchars($assignment['course_name']); ?>
                                            </h3>
                                            <?php if (!empty($assignment['title'])): ?>
                                                <p class="text-sm font-medium text-blue-600">
                                                    <?php echo htmlspecialchars($assignment['title']); ?>
                                                </p>
                                            <?php endif; ?>
                                            <p class="text-sm text-gray-500">
                                                <i class="fas fa-chalkboard-teacher mr-1"></i>
                                                <?php echo htmlspecialchars($assignment['class_name']); ?>
                                            </p>
                                        </div>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $status_config['class']; ?>">
                                            <i class="fas <?php echo $status_config['icon']; ?> mr-1"></i>
                                            <?php echo $status_config['text']; ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($assignment['description'])): ?>
                                        <p class="text-sm text-gray-600 mb-3">
                                            <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
                                        </p>
                                    <?php endif; ?>
                                    <div class="text-sm text-gray-500 mb-4">
                                        <p class="flex items-center">
                                            <i class="fas fa-calendar mr-2"></i>
                                            <?php echo $assignment['formatted_date']; ?>
                                        </p>
                                        <p class="flex items-center mt-1">
                                            <i class="fas fa-clock mr-2"></i>
                                            <?php echo $assignment['formatted_time']; ?>
                                        </p>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <a href="view_exam.php?course_id=<?php echo htmlspecialchars($assignment['course_id']); ?>" 
                                           class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-150 ease-in-out">
                                            <i class="fas fa-eye mr-2"></i>
                                            Voir les détails
                                        </a>
                                        <?php if ($assignment['status'] === 'upcoming' && $assignment['created_by'] === $teacher_id): ?>
                                            <button onclick="deleteAssignment('<?php echo $assignment['id']; ?>')" 
                                                    class="inline-flex items-center px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition duration-150 ease-in-out">
                                                <i class="fas fa-trash mr-2"></i>
                                                Supprimer
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-gray-500">
                            Aucun devoir programmé pour cette classe.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Section des examens -->
            <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Examens programmés</h2>
                    <?php if (!empty($exams)): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($exams as $exam): 
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
                                <div class="border rounded-lg p-4 hover:shadow-md transition duration-150 ease-in-out">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h3 class="font-medium text-gray-900 mb-1">
                                                <?php echo htmlspecialchars($exam['course_name']); ?>
                                            </h3>
                                            <?php if (!empty($exam['title'])): ?>
                                                <p class="text-sm font-medium text-blue-600">
                                                    <?php echo htmlspecialchars($exam['title']); ?>
                                                </p>
                                            <?php endif; ?>
                                            <p class="text-sm text-gray-500">
                                                <i class="fas fa-chalkboard-teacher mr-1"></i>
                                                <?php echo htmlspecialchars($exam['class_name']); ?>
                                            </p>
                                        </div>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $status_config['class']; ?>">
                                            <i class="fas <?php echo $status_config['icon']; ?> mr-1"></i>
                                            <?php echo $status_config['text']; ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($exam['description'])): ?>
                                        <p class="text-sm text-gray-600 mb-3">
                                            <?php echo nl2br(htmlspecialchars($exam['description'])); ?>
                                        </p>
                                    <?php endif; ?>
                                    <div class="text-sm text-gray-500 mb-4">
                                        <p class="flex items-center">
                                            <i class="fas fa-calendar mr-2"></i>
                                            <?php echo $exam['formatted_date']; ?>
                                        </p>
                                        <p class="flex items-center mt-1">
                                            <i class="fas fa-clock mr-2"></i>
                                            <?php echo $exam['formatted_time']; ?>
                                        </p>
                                    </div>
                                    <div class="flex justify-end">
                                        <a href="view_exam.php?course_id=<?php echo htmlspecialchars($exam['course_id']); ?>" 
                                           class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition duration-150 ease-in-out">
                                            <i class="fas fa-eye mr-2"></i>
                                            Voir les détails
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-gray-500">
                            Aucun examen programmé pour cette classe.
                        </div>
                    <?php endif; ?>
                </div>
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

    <script>
        function updateFilters() {
            const form = document.getElementById('filterForm');
            form.submit();
        }

        function deleteAssignment(assignmentId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce devoir ?')) {
                window.location.href = `delete_assignment.php?id=${assignmentId}`;
            }
        }
    </script>
</body>
</html>
