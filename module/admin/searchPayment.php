<?php
include_once('main.php');
require_once('../../db/config.php');

// Get admin ID for filtering
$admin_id = $_SESSION['login_id'];

// Initialize database connection
$conn = getDbConnection();

$searchKey = $_GET['key'];

// Search payments with admin filtering
$sql = "SELECT p.*, s.name as student_name 
        FROM payment p 
        INNER JOIN students s ON p.studentid = s.id 
        WHERE (p.id LIKE ? OR p.studentid LIKE ?) 
        AND s.created_by = ? 
        AND p.month = MONTH(CURRENT_DATE()) 
        AND p.year = YEAR(CURRENT_DATE())
        ORDER BY p.id DESC";

$searchPattern = $searchKey . '%';
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $searchPattern, $searchPattern, $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0): ?>
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
        Aucun paiement trouvé
    </div>
<?php endif;

// Close database connection
$stmt->close();
$conn->close();
?>
