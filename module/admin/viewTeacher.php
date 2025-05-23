<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];
$teacher_id = $_GET['id'] ?? '';
$error_message = '';
$teacher_data = null;

// Vérifier si l'enseignant existe et appartient à cet admin
if ($teacher_id) {
    $sql = "SELECT t.*, u.userid as creator_id 
            FROM teachers t 
            LEFT JOIN users u ON t.created_by = u.userid 
            WHERE t.id = ? AND t.created_by = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("ss", $teacher_id, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher_data = $result->fetch_assoc();

    if (!$teacher_data) {
        header("Location: manageTeacher.php?error=" . urlencode("Enseignant non trouvé ou accès non autorisé"));
        exit;
    }
}

// Récupérer les cours de l'enseignant
$courses_sql = "SELECT c.id as courseid, c.name as coursename, c.classid 
                FROM course c 
                INNER JOIN takencoursebyteacher tc ON c.id = tc.courseid 
                WHERE tc.teacherid = ?";
$courses_stmt = $link->prepare($courses_sql);
$courses_stmt->bind_param("s", $teacher_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-900">Détails de l\'Enseignant</h2>
            <a href="manageTeacher.php" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
            </a>
        </div>

        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <!-- Informations personnelles -->
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations Personnelles</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm font-medium text-gray-500">ID</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($teacher_data['id']) . '</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Nom</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($teacher_data['name']) . '</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Email</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($teacher_data['email']) . '</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Téléphone</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($teacher_data['phone']) . '</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Genre</p>
                        <p class="mt-1">
                            ' . ($teacher_data['sex'] == 'female' ? 
                                '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-pink-100 text-pink-800">Femme</span>' : 
                                '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Homme</span>') . '
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Date de naissance</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($teacher_data['dob']) . '</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Date d\'embauche</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($teacher_data['hiredate']) . '</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Salaire</p>
                        <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($teacher_data['salary']) . ' €</p>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-sm font-medium text-gray-500">Adresse</p>
                    <p class="mt-1 text-sm text-gray-900">' . htmlspecialchars($teacher_data['address']) . '</p>
                </div>
            </div>

            <!-- Cours enseignés -->
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Cours Enseignés</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom du cours</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Classe</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">';

                        if ($courses_result->num_rows > 0) {
                            while ($course = $courses_result->fetch_assoc()) {
                                $content .= '
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">' . 
                                        htmlspecialchars($course['courseid']) . '</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . 
                                        htmlspecialchars($course['coursename']) . '</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . 
                                        htmlspecialchars($course['classid']) . '</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="viewCourse.php?id=' . htmlspecialchars($course['courseid']) . '" 
                                           class="text-blue-600 hover:text-blue-900">Voir le cours</a>
                                    </td>
                                </tr>';
                            }
                        } else {
                            $content .= '
                            <tr>
                                <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    Aucun cours assigné à cet enseignant
                                </td>
                            </tr>';
                        }

$content .= '
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Boutons d\'action -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                <a href="updateTeacher.php?id=' . htmlspecialchars($teacher_id) . '" 
                   class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Modifier
                </a>
                <button onclick="confirmDelete(\'' . htmlspecialchars($teacher_id) . '\')"
                        class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Supprimer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(teacherId) {
    if (confirm("Êtes-vous sûr de vouloir supprimer cet enseignant ?")) {
        window.location.href = "deleteTeacher.php?id=" + teacherId;
    }
}
</script>';

include('templates/layout.php');
?>
