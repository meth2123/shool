<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];
$course_id = $_GET['id'] ?? '';

// Récupérer les informations du cours
$sql = "SELECT c.*, t.name as teacher_name, cl.name as class_name 
        FROM course c 
        LEFT JOIN teachers t ON c.teacherid = t.id 
        LEFT JOIN class cl ON c.classid = cl.id 
        WHERE c.id = ? AND c.created_by = ?";

$stmt = $link->prepare($sql);
$stmt->bind_param("ss", $course_id, $admin_id);
$stmt->execute();
$course_result = $stmt->get_result();
$course = $course_result->fetch_assoc();

if (!$course) {
    header("Location: course.php?error=" . urlencode("Cours non trouvé ou accès non autorisé"));
    exit;
}

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-900">Détails du Cours</h2>
            <a href="course.php" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
            </a>
        </div>

        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <!-- Informations du cours -->
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm font-medium text-gray-500">ID du cours</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($course['id']) . '</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Nom du cours</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($course['name']) . '</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Enseignant</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($course['teacher_name'] ?? 'Non assigné') . '</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Classe</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($course['class_name'] ?? 'Non assignée') . '</p>
                    </div>
                </div>
            </div>

            <!-- Boutons d\'action -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                <a href="updateCourse.php?id=' . htmlspecialchars($course_id) . '" 
                   class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Modifier le cours
                </a>
                <button onclick="confirmDelete(\'' . htmlspecialchars($course_id) . '\')"
                        class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Supprimer le cours
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(courseId) {
    if (confirm("Êtes-vous sûr de vouloir supprimer ce cours ?")) {
        window.location.href = "deleteCourse.php?id=" + courseId;
    }
}
</script>';

include('templates/layout.php');
?>
