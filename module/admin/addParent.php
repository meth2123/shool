<?php
include_once('main.php');
require_once('../../db/config.php');

// Get admin ID for filtering
$admin_id = $_SESSION['login_id'];

// Initialize database connection
$conn = getDbConnection();

// Enable error reporting for mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Function to generate next parent ID
function generateNextParentId($conn, $mothername) {
    try {
        // Extraire les 3 premières lettres du nom de la mère et les mettre en majuscules
        $mother_prefix = strtoupper(substr(trim($mothername), 0, 3));
        
        $sql = "SELECT id FROM parents WHERE id LIKE ? ORDER BY id DESC LIMIT 1";
        $pattern = $mother_prefix . '-PA%';
        error_log("Executing SQL: " . $sql . " with pattern: " . $pattern);
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $lastId = $row['id'];
            // Extraire le numéro après le dernier tiret
            $number = intval(substr($lastId, strrpos($lastId, '-') + 3)) + 1;
        } else {
            $number = 1;
        }
        
        // Format: DIO-PA001, DIO-PA002, etc. (pour DIOP par exemple)
        return $mother_prefix . '-PA' . str_pad($number, 3, '0', STR_PAD_LEFT);
    } catch (Exception $e) {
        error_log("Error in generateNextParentId: " . $e->getMessage());
        throw $e;
    }
}

// Get admin name
$sql = "SELECT name FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$login_session = $loged_user_name = $admin['name'];

if(!isset($login_session)){
    header("Location:../../");
    exit;
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    try {
        // Validate and sanitize input first
        $password = trim($_POST['password']);
        $fathername = trim($_POST['fathername']);
        $mothername = trim($_POST['mothername']);
        $fatherphone = trim($_POST['fatherphone']);
        $motherphone = trim($_POST['motherphone']);
        $address = trim($_POST['address']);

        // Basic validation
        if (empty($password) || empty($fathername) || empty($fatherphone) || empty($mothername)) {
            throw new Exception("Veuillez remplir tous les champs obligatoires, y compris le nom de la mère");
        }

        // Generate new parent ID using mother's name
        $id = generateNextParentId($conn, $mothername);
        
        // Debug output
        error_log("=== Debug addParent.php ===");
        error_log("Mother's name: " . $mothername);
        error_log("Generated Parent ID: " . $id);
        error_log("Form Data: " . print_r([
            'id' => $id,
            'password' => $password,
            'fathername' => $fathername,
            'mothername' => $mothername,
            'fatherphone' => $fatherphone,
            'motherphone' => $motherphone,
            'address' => $address,
            'created_by' => $admin_id
        ], true));

        // Start transaction
        $conn->begin_transaction();

        // Check if ID already exists
        $check_sql = "SELECT id FROM parents WHERE id = ?";
        error_log("Executing check SQL: " . $check_sql . " with ID: " . $id);
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            throw new Exception("Cet identifiant existe déjà");
        }

        // Insert into parents table
        $sql = "INSERT INTO parents (id, password, fathername, mothername, fatherphone, motherphone, address, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        error_log("Executing insert SQL: " . $sql);
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erreur de préparation de la requête : " . $conn->error);
        }
        
        $stmt->bind_param("ssssssss", $id, $password, $fathername, $mothername, $fatherphone, $motherphone, $address, $admin_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de l'ajout du parent : " . $stmt->error);
        }

        // Insert into users table
        $sql_user = "INSERT INTO users (userid, password, usertype) VALUES (?, ?, 'parent')";
        error_log("Executing user insert SQL: " . $sql_user);
        $stmt_user = $conn->prepare($sql_user);
        if (!$stmt_user) {
            throw new Exception("Erreur de préparation de la requête utilisateur : " . $conn->error);
        }
        
        $stmt_user->bind_param("ss", $id, $password);
        
        if (!$stmt_user->execute()) {
            throw new Exception("Erreur lors de la création du compte utilisateur : " . $stmt_user->error);
        }

        // Commit transaction
        $conn->commit();
        $success_message = "Parent ajouté avec succès";
        error_log("Transaction committed successfully");
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($conn)) {
            $conn->rollback();
        }
        $error_message = $e->getMessage();
        error_log("Error in addParent.php: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Parent</title>
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
                    <span class="mr-4">Bonjour, <?php echo htmlspecialchars($login_session);?></span>
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
                <a href="manageParent.php" class="text-gray-600 hover:text-blue-500 px-3 py-2 rounded-md">
                    <i class="fas fa-users mr-2"></i>Gestion des Parents
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                    <i class="fas fa-user-plus mr-2 text-blue-500"></i>
                    Ajouter un Parent
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="fathername" class="block text-sm font-medium text-gray-700">Nom du père *</label>
                            <input type="text" id="fathername" name="fathername" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="Entrez le nom du père">
                        </div>

                        <div>
                            <label for="mothername" class="block text-sm font-medium text-gray-700">Nom de la mère</label>
                            <input type="text" id="mothername" name="mothername"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="Entrez le nom de la mère">
                        </div>

                        <div>
                            <label for="fatherphone" class="block text-sm font-medium text-gray-700">Téléphone du père *</label>
                            <input type="tel" id="fatherphone" name="fatherphone" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="Entrez le numéro de téléphone">
                        </div>

                        <div>
                            <label for="motherphone" class="block text-sm font-medium text-gray-700">Téléphone de la mère</label>
                            <input type="tel" id="motherphone" name="motherphone"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="Entrez le numéro de téléphone">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Mot de passe *</label>
                            <input type="password" id="password" name="password" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="Entrez le mot de passe">
                        </div>
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700">Adresse *</label>
                        <textarea id="address" name="address" required rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Entrez l'adresse"></textarea>
				</div>

                    <div class="flex justify-end">
                        <button type="submit" name="submit"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition duration-200">
                            <i class="fas fa-save mr-2"></i>
                            Enregistrer
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
if (isset($stmt_user)) $stmt_user->close();
if (isset($check_stmt)) $check_stmt->close();
if (isset($conn)) $conn->close();
?>
