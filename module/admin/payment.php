<?php
include_once('main.php');
require_once('../../db/config.php');
include_once('../../service/db_utils.php');

// Debug connection info
error_log("=== Debug Payment.php ===");

// Get admin ID for filtering
$admin_id = $_SESSION['login_id'];
error_log("Admin ID from session: " . $admin_id);

// Initialize database connection
$conn = getDbConnection();
if (!$conn) {
    error_log("Database connection failed!");
} else {
    error_log("Database connection successful");
    
    // Debug query to see all payments
    $debug_sql = "SELECT p.*, s.name as student_name 
                  FROM payment p 
                  INNER JOIN students s ON p.studentid = s.id 
                  LIMIT 5";
    $debug_result = $conn->query($debug_sql);
    error_log("=== All Payments (Debug) ===");
    if ($debug_result && $debug_result->num_rows > 0) {
        while ($row = $debug_result->fetch_assoc()) {
            error_log("Payment ID: " . $row['id'] . 
                     ", Student: " . $row['student_name'] . 
                     ", Created by: " . (isset($row['created_by']) ? $row['created_by'] : 'NULL'));
        }
    } else {
        error_log("No payments found at all in the database!");
    }
    
    // Check if created_by column exists
    $check_column_sql = "SHOW COLUMNS FROM payment LIKE 'created_by'";
    $column_result = $conn->query($check_column_sql);
    if ($column_result && $column_result->num_rows > 0) {
        error_log("created_by column exists in payment table");
    } else {
        error_log("created_by column does NOT exist in payment table!");
    }
}

// Get payments for current month/year and only for students created by this admin
$sql = "SELECT p.*, s.name as student_name 
        FROM payment p 
        INNER JOIN students s ON p.studentid = s.id 
        WHERE p.created_by = ?
        ORDER BY p.id DESC";

error_log("SQL Query: " . $sql);
error_log("Admin ID for query: " . $admin_id);

try {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
    } else {
        $stmt->bind_param("s", $admin_id);
        $success = $stmt->execute();
        if (!$success) {
            error_log("Execute failed: " . $stmt->error);
        } else {
            $result = $stmt->get_result();
            error_log("Number of rows found: " . $result->num_rows);
            
            // Debug first row if exists
            if ($result->num_rows > 0) {
                $firstRow = $result->fetch_assoc();
                error_log("First payment found: " . print_r($firstRow, true));
                $result->data_seek(0); // Reset pointer
            }
        }
    }
} catch (Exception $e) {
    error_log("Exception occurred: " . $e->getMessage());
}

// Debug result
error_log("Number of rows found: " . $result->num_rows);

while ($row = $result->fetch_assoc()) {
    error_log("Payment ID: " . $row['id'] . 
              ", Student: " . $row['student_name'] . 
              ", Month: " . $row['month'] . 
              ", Year: " . $row['year'] . 
              ", Created by: " . $row['created_by']);
}

// Reset result pointer
$result->data_seek(0);

// Close database connection
$stmt->close();
$conn->close();

// Vérifier si l'utilisateur est connecté en tant qu'admin
if (!isset($check) || !str_starts_with($check, 'ad-')) {
    header("Location: ../../?error=unauthorized");
    exit();
}

// Fonction pour obtenir les classes
function getClasses() {
    return db_fetch_all("SELECT * FROM class ORDER BY name");
}

// Fonction pour obtenir les montants des paiements par classe
function getClassPaymentAmounts() {
    return db_fetch_all("
        SELECT cpa.*, c.name as class_name 
        FROM class_payment_amount cpa 
        JOIN class c ON cpa.class_id = c.id 
        ORDER BY c.name
    ");
}

// Fonction pour obtenir l'historique des paiements
function getPaymentHistory($filters = []) {
    $query = "
        SELECT p.*, 
               s.name as student_name,
               c.name as class_name,
               CASE 
                   WHEN p.created_by LIKE 'ad-%' THEN 'Administration'
                   WHEN p.created_by LIKE 'pa-%' THEN 'Parent'
                   ELSE 'Autre'
               END as payment_source,
               a.name as admin_name
        FROM payment p
        JOIN students s ON p.studentid = s.id
        JOIN class c ON s.classid = c.id
        LEFT JOIN admin a ON p.created_by = a.id
        WHERE 1=1
    ";
    $params = [];
    $types = '';

    if (!empty($filters['class_id'])) {
        $query .= " AND s.classid = ?";
        $params[] = $filters['class_id'];
        $types .= 's';
    }

    if (!empty($filters['year'])) {
        $query .= " AND p.year = ?";
        $params[] = $filters['year'];
        $types .= 'i';
    }

    if (!empty($filters['month'])) {
        $query .= " AND p.month = ?";
        $params[] = $filters['month'];
        $types .= 'i';
    }

    $query .= " ORDER BY p.year DESC, p.month DESC, s.name ASC";

    return db_fetch_all($query, $params, $types);
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'set_payment_amount':
                $class_id = $_POST['class_id'];
                $amount = $_POST['amount'];
                
                // Vérifier si un montant existe déjà pour cette classe
                $existing = db_fetch_row(
                    "SELECT id FROM class_payment_amount WHERE class_id = ?",
                    [$class_id],
                    's'
                );
                
                if ($existing) {
                    // Mettre à jour le montant existant
                    db_execute(
                        "UPDATE class_payment_amount SET amount = ? WHERE class_id = ?",
                        [$amount, $class_id],
                        'ds'
                    );
                } else {
                    // Insérer un nouveau montant
                    db_execute(
                        "INSERT INTO class_payment_amount (class_id, amount) VALUES (?, ?)",
                        [$class_id, $amount],
                        'sd'
                    );
                }
                break;
        }
    }
}

// Récupérer les données pour l'affichage
$classes = getClasses();
$paymentAmounts = getClassPaymentAmounts();
$paymentHistory = getPaymentHistory($_GET);

// Créer la table class_payment_amount si elle n'existe pas
db_execute("
    CREATE TABLE IF NOT EXISTS class_payment_amount (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_id VARCHAR(20) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (class_id) REFERENCES class(id) ON DELETE CASCADE,
        UNIQUE KEY unique_class (class_id)
    )
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Paiements - Administration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="JS/login_logout.js"></script>
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
                <a href="addPayment.php" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-plus-circle mr-2"></i>Ajouter un Paiement
                </a>
                <a href="deletePayment.php" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-trash mr-2"></i>Supprimer un Paiement
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                <i class="fas fa-money-bill-wave mr-2 text-blue-500"></i>
                Paiements du Mois en Cours
            </h2>

            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Étudiant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mois</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Année</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['id']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['student_name']) . " (" . htmlspecialchars($row['studentid']) . ")"; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo number_format($row['amount'], 2) . " €"; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['month']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['year']); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Aucun paiement trouvé pour ce mois
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section 1: Gestion des montants des paiements -->
    <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8">
        <div class="p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Configuration des montants des paiements</h2>
            
            <!-- Formulaire pour définir les montants -->
            <form method="POST" class="mb-8">
                <input type="hidden" name="action" value="set_payment_amount">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Classe</label>
                        <select name="class_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Sélectionner une classe</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo htmlspecialchars($class['id']); ?>">
                                    <?php echo htmlspecialchars($class['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Montant mensuel (€)</label>
                        <input type="number" name="amount" step="0.01" min="0" required
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-save mr-2"></i>Enregistrer
                        </button>
                    </div>
                </div>
            </form>

            <!-- Tableau des montants actuels -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Classe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant mensuel</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dernière mise à jour</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($paymentAmounts as $amount): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($amount['class_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo number_format($amount['amount'], 2); ?> €
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d/m/Y H:i', strtotime($amount['updated_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section 2: Historique des paiements -->
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Historique des paiements</h2>

            <!-- Filtres -->
            <form method="GET" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Classe</label>
                        <select name="class_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Toutes les classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo htmlspecialchars($class['id']); ?>"
                                        <?php echo isset($_GET['class_id']) && $_GET['class_id'] === $class['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Année</label>
                        <select name="year" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Toutes les années</option>
                            <?php 
                                $currentYear = date('Y');
                                for ($year = $currentYear; $year >= $currentYear - 2; $year--) {
                                    echo '<option value="' . $year . '"' . 
                                         (isset($_GET['year']) && $_GET['year'] == $year ? ' selected' : '') . '>' . 
                                         $year . '</option>';
                                }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mois</label>
                        <select name="month" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Tous les mois</option>
                            <?php 
                                $months = [
                                    10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
                                    1 => 'Janvier', 2 => 'Février', 3 => 'Mars',
                                    4 => 'Avril', 5 => 'Mai', 6 => 'Juin'
                                ];
                                foreach ($months as $num => $name) {
                                    echo '<option value="' . $num . '"' . 
                                         (isset($_GET['month']) && $_GET['month'] == $num ? ' selected' : '') . '>' . 
                                         $name . '</option>';
                                }
                            ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-filter mr-2"></i>Filtrer
                        </button>
                    </div>
                </div>
            </form>

            <!-- Tableau des paiements -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Étudiant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Classe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mois</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Année</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Effectué par</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($paymentHistory as $payment): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($payment['student_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($payment['class_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php 
                                        $months = [
                                            10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
                                            1 => 'Janvier', 2 => 'Février', 3 => 'Mars',
                                            4 => 'Avril', 5 => 'Mai', 6 => 'Juin'
                                        ];
                                        echo $months[$payment['month']] ?? $payment['month'];
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $payment['year']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo number_format($payment['amount'], 2); ?> €
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php 
                                            echo $payment['payment_source'] === 'Administration' 
                                                ? 'bg-blue-100 text-blue-800' 
                                                : ($payment['payment_source'] === 'Parent' 
                                                    ? 'bg-green-100 text-green-800' 
                                                    : 'bg-gray-100 text-gray-800');
                                        ?>">
                                        <?php echo htmlspecialchars($payment['payment_source']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php 
                                        if ($payment['payment_source'] === 'Administration' && $payment['admin_name']) {
                                            echo htmlspecialchars($payment['admin_name']);
                                        } else {
                                            echo '-';
                                        }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php 
                                        if (isset($payment['created_at'])) {
                                            echo date('d/m/Y H:i', strtotime($payment['created_at']));
                                        } else {
                                            echo date('d/m/Y', strtotime($payment['year'] . '-' . $payment['month'] . '-01'));
                                        }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="bg-white shadow-lg mt-8">
    <div class="max-w-7xl mx-auto py-4 px-4">
        <p class="text-center text-gray-500 text-sm">
            © <?php echo date('Y'); ?> Système de Gestion Scolaire. Tous droits réservés.
        </p>
    </div>
</footer>
</body>
</html>
