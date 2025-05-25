<?php
include_once('main.php');
include_once('includes/auth_check.php');
require_once('../../db/config.php');

// L'ID de l'administrateur est déjà défini dans auth_check.php
$admin_id = $_SESSION['login_id'];

// Initialize database connection
$conn = getDbConnection();

// Get list of students created by this admin
$sql = "SELECT id, name FROM students WHERE created_by = ? ORDER BY name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$students_result = $stmt->get_result();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    try {
        // Validate input
        $student_id = trim($_POST['student_id']);
        $amount = floatval($_POST['amount']);
        $month = intval($_POST['month']);
        $year = intval($_POST['year']);

        // Basic validation
        if (empty($student_id) || $amount <= 0 || $month < 1 || $month > 12 || $year < 2000) {
            throw new Exception("Veuillez saisir des informations de paiement valides");
        }

        // Verify student exists and belongs to this admin
        $check_student = "SELECT id FROM students WHERE id = ? AND created_by = ?";
        $stmt = $conn->prepare($check_student);
        $stmt->bind_param("ss", $student_id, $admin_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            throw new Exception("ID étudiant non trouvé ou non autorisé");
        }

        // Insert payment using prepared statement with created_by
        $sql = "INSERT INTO payment (studentid, amount, month, year, created_by) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdiis", $student_id, $amount, $month, $year, $admin_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors du traitement du paiement");
        }

        $success_message = "Paiement traité avec succès";
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Paiement</title>
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
                <a href="payment.php" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-list mr-2"></i>Liste des Paiements
                </a>
                <a href="deletePayment.php" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-trash mr-2"></i>Supprimer un Paiement
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                    <i class="fas fa-plus-circle mr-2 text-blue-500"></i>
                    Ajouter un Paiement
                </h2>

                <?php if ($success_message): ?>
                    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form action="" method="post" class="space-y-6">
                    <div>
                        <label for="student_id" class="block text-sm font-medium text-gray-700">Étudiant</label>
                        <select id="student_id" name="student_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Sélectionnez un étudiant</option>
                            <?php while ($student = $students_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($student['id']); ?>">
                                    <?php echo htmlspecialchars($student['name']) . ' (' . htmlspecialchars($student['id']) . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700">Montant</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">€</span>
                            </div>
                            <input type="number" name="amount" id="amount" required step="0.01" min="0"
                                   class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="0.00">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="month" class="block text-sm font-medium text-gray-700">Mois</label>
                            <select id="month" name="month" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <?php
                                $months = [
                                    1 => 'Janvier', 2 => 'Février', 3 => 'Mars',
                                    4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
                                    7 => 'Juillet', 8 => 'Août', 9 => 'Septembre',
                                    10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
                                ];
                                foreach ($months as $num => $name): ?>
                                    <option value="<?php echo $num; ?>" <?php echo date('n') == $num ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="year" class="block text-sm font-medium text-gray-700">Année</label>
                            <select id="year" name="year" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <?php
                                $current_year = intval(date('Y'));
                                for ($year = $current_year; $year >= $current_year - 5; $year--): ?>
                                    <option value="<?php echo $year; ?>" <?php echo $current_year == $year ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
				</div>

                    <div class="flex justify-end">
                        <button type="submit" name="submit"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition duration-200">
                            <i class="fas fa-save mr-2"></i>
                            Enregistrer le Paiement
                        </button>
						    </div>
            </form>
            </div>
        </div>
    </div>
		</body>
</html>
<?php
// Close database connection
if (isset($stmt)) $stmt->close();
if (isset($conn)) $conn->close();
?>
