<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification de la session admin
if (!isset($check)) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Erreur!</strong>
            <span class="block sm:inline">Accès non autorisé.</span>
          </div>';
    exit();
}

$success_message = '';
$error_message = '';
$selected_class = $_GET['class_id'] ?? '';

// Récupération de toutes les classes
$classes = db_fetch_all(
    "SELECT * FROM class ORDER BY name",
    [],
    ''
);

// Récupération des cours pour la classe sélectionnée
$class_courses = [];
if ($selected_class) {
    $class_courses = db_fetch_all(
        "SELECT DISTINCT c.*, 
            COALESCE(c.coefficient, 1) as current_coefficient,
            COUNT(DISTINCT stc.student_id) as student_count
         FROM course c 
         LEFT JOIN student_teacher_course stc ON c.id = stc.course_id 
            AND stc.class_id = ?
         WHERE c.classid = ?
         GROUP BY c.id
         ORDER BY c.name",
        [$selected_class, $selected_class],
        'ss'
    );
}

// Traitement de la soumission des coefficients
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_coefficients'])) {
    try {
        foreach ($_POST['coefficients'] as $course_id => $coefficient) {
            if (is_numeric($coefficient) && $coefficient > 0) {
                // Mise à jour du coefficient dans la table course
                db_query(
                    "UPDATE course 
                     SET coefficient = ?, 
                         updated_at = NOW() 
                     WHERE id = ?",
                    [$coefficient, $course_id],
                    'ds'
                );

                // Mise à jour des coefficients dans student_teacher_course pour les examens
                db_query(
                    "UPDATE student_teacher_course 
                     SET coefficient = ? 
                     WHERE course_id = ? 
                     AND grade_type = 'examen'",
                    [$coefficient, $course_id],
                    'ds'
                );
            }
        }
        $success_message = "Les coefficients ont été mis à jour avec succès.";
    } catch (Exception $e) {
        $error_message = "Une erreur est survenue lors de la mise à jour des coefficients: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Gestion des Coefficients - Administration</title>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Gestion des Coefficients</h1>
            <a href="index.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700">
                Retour au tableau de bord
            </a>
        </div>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire de sélection de la classe -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="class_id" class="block text-sm font-medium text-gray-700 mb-2">Classe</label>
                    <select name="class_id" id="class_id" onchange="this.form.submit()"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Sélectionner une classe</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo htmlspecialchars($class['id']); ?>"
                                    <?php echo $selected_class === $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($selected_class && !empty($class_courses)): ?>
            <!-- Formulaire des coefficients -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">Coefficients des matières</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Définissez les coefficients pour chaque matière. Ces coefficients seront utilisés pour calculer les moyennes.
                    </p>
                </div>
                
                <form method="POST" class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matière</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre d'élèves</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coefficient actuel</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nouveau coefficient</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($class_courses as $course): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($course['name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($course['student_count']); ?> élèves
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($course['current_coefficient']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <input type="number" 
                                                   name="coefficients[<?php echo htmlspecialchars($course['id']); ?>]"
                                                   value="<?php echo htmlspecialchars($course['current_coefficient']); ?>"
                                                   min="0.5" 
                                                   max="5" 
                                                   step="0.5"
                                                   class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-24 sm:text-sm border-gray-300 rounded-md">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button type="submit" 
                                name="submit_coefficients"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Enregistrer les coefficients
                        </button>
                    </div>
                </form>
            </div>
        <?php elseif ($selected_class): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative">
                Aucune matière trouvée pour cette classe.
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 