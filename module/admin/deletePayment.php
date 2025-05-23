<?php
include_once('main.php');
require_once('../../db/config.php');

// Get admin ID for filtering
$admin_id = $_SESSION['login_id'];

// Initialize database connection
$conn = getDbConnection();

// Get current month's payments for this admin
$sql = "SELECT p.*, s.name as student_name 
        FROM payment p 
        INNER JOIN students s ON p.studentid = s.id 
        WHERE p.month = MONTH(CURRENT_DATE()) 
        AND p.year = YEAR(CURRENT_DATE())
        AND s.created_by = ?
        ORDER BY p.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

// Close database connection
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer un Paiement</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="JS/login_logout.js"></script>
    <script src="JS/searchPayment.js"></script>
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
                    <span class="mr-4">Bonjour, <?php echo htmlspecialchars($check);?></span>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="bg-white shadow-md mt-4">
        <div class="container mx-auto px-4">
            <div class="flex space-x-4 py-4">
                <a href="index.php" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-home mr-2"></i>Accueil
                </a>
                <a href="payment.php" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-list mr-2"></i>Liste des Paiements
                </a>
                <a href="addPayment.php" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-plus-circle mr-2"></i>Ajouter un Paiement
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                    <i class="fas fa-trash mr-2 text-red-500"></i>
                    Supprimer un Paiement
                </h2>

                <!-- Search Box -->
                <div class="mb-6">
                    <label for="searchPayment" class="block text-sm font-medium text-gray-700 mb-2">
                        Rechercher par ID de Paiement ou ID Étudiant
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" id="searchPayment" 
                               class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               placeholder="Entrez l'ID du paiement ou de l'étudiant"
                               onkeyup="getPayment(this.value);">
                    </div>
                </div>

                <!-- Search Results -->
                <div id="paymentList" class="mt-4">
                    <?php if ($result->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Étudiant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mois</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Année</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <form action="deletePaymentableData.php" method="post" class="inline-block">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900 transition duration-200">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['id']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['student_name']) . ' (' . htmlspecialchars($row['studentid']) . ')'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo number_format($row['amount'], 2) . ' €'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['month']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['year']); ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4 text-gray-500">
                        <i class="fas fa-info-circle mr-2"></i>
                        Aucun paiement trouvé pour ce mois
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Confirmation avant suppression
    document.addEventListener('submit', function(e) {
        if (e.target.action.includes('deletePaymentableData.php')) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce paiement ?')) {
                e.preventDefault();
            }
        }
    });
    </script>
</body>
</html>
