<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification des droits d'administrateur
if (!isset($check) || !isset($login_session)) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Erreur!</strong>
            <span class="block sm:inline">Accès non autorisé.</span>
          </div>';
    exit();
}

// Vérification supplémentaire que l'utilisateur est bien un admin
$is_admin = db_fetch_row(
    "SELECT 1 FROM admin WHERE id = ?",
    [$check],
    's'
);

if (!$is_admin) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Erreur!</strong>
            <span class="block sm:inline">Accès réservé aux administrateurs.</span>
          </div>';
    exit();
}

// Traitement de l'assignation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_course'])) {
    $teacher_id = $_POST['teacher_id'];
    $course_id = $_POST['course_id'];
    
    // Vérifier si l'assignation existe déjà
    $exists = db_fetch_row(
        "SELECT 1 FROM takencoursebyteacher 
         WHERE teacherid = ? AND courseid = ?",
        [$teacher_id, $course_id],
        'ss'
    );
    
    if (!$exists) {
        // Créer l'assignation
        $result = db_execute(
            "INSERT INTO takencoursebyteacher (courseid, teacherid, created_by) 
             VALUES (?, ?, ?)",
            [$course_id, $teacher_id, $check],
            'sss'
        );
        
        if ($result) {
            $success_message = "Cours assigné avec succès à l'enseignant";
        } else {
            $error_message = "Erreur lors de l'assignation du cours";
        }
    } else {
        $error_message = "Ce cours est déjà assigné à cet enseignant";
    }
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_assignment'])) {
    $assignment_id = $_POST['delete_assignment'];
    
    // Vérifier que l'admin peut supprimer cette assignation
    $can_delete = db_fetch_row(
        "SELECT 1 FROM takencoursebyteacher WHERE id = ? AND created_by = ?",
        [$assignment_id, $check],
        'ss'
    );
    
    if ($can_delete) {
        $result = db_execute(
            "DELETE FROM takencoursebyteacher WHERE id = ? AND created_by = ?",
            [$assignment_id, $check],
            'ss'
        );
        
        if ($result) {
            $success_message = "Assignation supprimée avec succès";
        } else {
            $error_message = "Erreur lors de la suppression de l'assignation";
        }
    } else {
        $error_message = "Vous n'avez pas les droits pour supprimer cette assignation";
    }
}

// Récupération des enseignants créés par cet admin
$teachers = db_fetch_all(
    "SELECT id, name FROM teachers WHERE created_by = ? ORDER BY name",
    [$check],
    's'
);

// Récupération des cours créés par cet admin
$courses = db_fetch_all(
    "SELECT DISTINCT id, name FROM course WHERE created_by = ? ORDER BY name",
    [$check],
    's'
);

// Récupération des assignations créées par cet admin
$assignments = db_fetch_all(
    "SELECT t.id, t.courseid, t.teacherid,
            te.name as teacher_name,
            c.name as course_name
     FROM takencoursebyteacher t
     JOIN teachers te ON t.teacherid = te.id
     JOIN course c ON t.courseid = c.id
     WHERE t.created_by = ?
     ORDER BY te.name, c.name",
    [$check],
    's'
);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Assignation des Cours</title>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Assignation des Cours aux Enseignants</h1>

        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire d'assignation -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Nouvelle Assignation</h2>
                <?php if (empty($teachers) || empty($courses)): ?>
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative">
                        <p>Vous devez d'abord créer des enseignants et des cours avant de pouvoir faire des assignations.</p>
                        <div class="mt-4 space-x-4">
                            <a href="addTeacher.php" class="text-blue-600 hover:text-blue-800">Créer un enseignant</a>
                            <a href="addCourse.php" class="text-blue-600 hover:text-blue-800">Créer un cours</a>
                        </div>
                    </div>
                <?php else: ?>
                    <form method="POST" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Enseignant</label>
                                <select name="teacher_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Sélectionner un enseignant</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo htmlspecialchars($teacher['id'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($teacher['name'] ?? ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cours</label>
                                <select name="course_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Sélectionner un cours</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo htmlspecialchars($course['id'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($course['name'] ?? ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" name="assign_course" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Assigner le Cours
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Liste des assignations existantes -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-6">Vos Assignations</h2>
                
                <?php if (empty($assignments)): ?>
                    <p class="text-gray-600">Vous n'avez créé aucune assignation.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enseignant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cours</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($assignment['teacher_name'] ?? ''); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($assignment['course_name'] ?? ''); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <form method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette assignation ?');">
                                                <input type="hidden" name="delete_assignment" value="<?php echo htmlspecialchars($assignment['id'] ?? ''); ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900">
                                                    Supprimer
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 