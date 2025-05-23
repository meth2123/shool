<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

$admin_id = $_SESSION['login_id'];

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = $_POST['staff_id'] ?? '';
    $new_salary = $_POST['new_salary'] ?? '';
    
    if (!empty($staff_id) && !empty($new_salary)) {
        // Vérifier que le membre du personnel appartient à cet admin
        $check_sql = "SELECT id FROM staff WHERE id = ? AND created_by = ?";
        $check_stmt = $link->prepare($check_sql);
        $check_stmt->bind_param("ss", $staff_id, $admin_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            // Mettre à jour le salaire
            $update_sql = "UPDATE staff SET salary = ? WHERE id = ? AND created_by = ?";
            $update_stmt = $link->prepare($update_sql);
            $update_stmt->bind_param("dss", $new_salary, $staff_id, $admin_id);
            
            if ($update_stmt->execute()) {
                $success_message = "Le salaire a été mis à jour avec succès";
            } else {
                $error_message = "Erreur lors de la mise à jour du salaire";
            }
        } else {
            $error_message = "Membre du personnel non trouvé ou accès non autorisé";
        }
    }
}

// Récupérer la liste du personnel
$sql = "SELECT id, name, salary FROM staff WHERE created_by = ? ORDER BY name";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à jour des Salaires - Personnel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <div class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="../../source/logo.jpg" class="h-16 w-16 object-contain mr-4" alt="School Management System"/>
                    <h1 class="text-2xl font-bold text-gray-800">Système de Gestion Scolaire</h1>
                </div>
                <div class="flex items-center">
                    <a href="salary.php" class="text-blue-600 hover:text-blue-800 mr-4">
                        <i class="fas fa-arrow-left mr-2"></i>Retour aux salaires
                    </a>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Mise à jour des Salaires du Personnel</h2>

            <?php if (isset($success_message)): ?>
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salaire Actuel</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nouveau Salaire</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <form method="POST" action="" class="contents">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($row['id']); ?>
                                            <input type="hidden" name="staff_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($row['name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo number_format($row['salary'], 2); ?> €
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="number" name="new_salary" step="0.01" min="0" 
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                                   placeholder="Nouveau salaire" required>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <button type="submit" 
                                                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                <i class="fas fa-save mr-2"></i>Mettre à jour
                                            </button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Validation côté client
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const salaryInput = this.querySelector('input[name="new_salary"]');
            if (salaryInput.value <= 0) {
                e.preventDefault();
                alert('Le salaire doit être supérieur à 0');
            }
        });
    });
    </script>
</body>
</html>
