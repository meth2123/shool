<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

$check = $_SESSION['login_id'];
$stmt = $link->prepare("SELECT name FROM admin WHERE id = ?");
$stmt->bind_param("s", $check);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$login_session = $loged_user_name = $row['name'] ?? '';

if(!isset($login_session)){
    header("Location:../../");
    exit;
}

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Gestion des Présences</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Présence des Enseignants -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Enseignants</h3>
                <a href="teacherAttendance.php" 
                   class="block w-full text-center bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Gérer les présences
                </a>
            </div>

            <!-- Présence du Personnel -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Personnel</h3>
                <a href="staffAttendance.php" 
                   class="block w-full text-center bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                    Gérer les présences
                </a>
            </div>
        </div>

        <!-- Voir les Présences -->
        <div class="mt-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Historique des Présences</h3>
                <a href="viewAttendance.php" 
                   class="block w-full text-center bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                    Consulter l\'historique
                </a>
            </div>
        </div>
    </div>
</div>';

include('templates/layout.php');
?>
