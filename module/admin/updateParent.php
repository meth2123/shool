<?php
include_once('main.php');
require_once('../../db/config.php');

// Get admin ID for filtering
$admin_id = $_SESSION['login_id'];

// Initialize database connection
$conn = getDbConnection();

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

// Handle form submission
if(!empty($_POST['submit'])){
    try {
        $id = trim($_POST['id']);
        
        // Verify that this parent was created by the current admin
        $check_sql = "SELECT id FROM parents WHERE id = ? AND created_by = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $id, $admin_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows === 0) {
            throw new Exception("Vous n'êtes pas autorisé à modifier ce parent");
        }

        $password = trim($_POST['password']);
        $fathername = trim($_POST['fathername']);
        $mothername = trim($_POST['mothername']);
        $fatherphone = trim($_POST['fatherphone']);
        $motherphone = trim($_POST['motherphone']);
        $address = trim($_POST['address']);

        // Update parents table
        $sql = "UPDATE parents SET password=?, fathername=?, mothername=?, fatherphone=?, motherphone=?, address=? WHERE id=? AND created_by=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $password, $fathername, $mothername, $fatherphone, $motherphone, $address, $id, $admin_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la mise à jour du parent");
        }

        // Update users table
        $sql_user = "UPDATE users SET password=? WHERE userid=?";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("ss", $password, $id);
        
        if (!$stmt_user->execute()) {
            throw new Exception("Erreur lors de la mise à jour du compte utilisateur");
        }

        $success_message = "Parent mis à jour avec succès";
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
    <title>Modifier un Parent</title>
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
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">
                    <i class="fas fa-user-edit mr-2 text-blue-500"></i>
                    Modifier un Parent
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

                <!-- Search Section -->
                <div class="mb-6">
                    <label for="searchId" class="block text-sm font-medium text-gray-700 mb-2">Rechercher un parent :</label>
                    <input type="text" id="searchId" 
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Entrez l'ID ou le nom du parent"
                           onkeyup="getParentForUpdate(this.value);">
                </div>

                <!-- Update Form -->
                <form id="updateParentData" method="post" class="space-y-6">
                    <!-- Le contenu du formulaire sera rempli dynamiquement par JavaScript -->
                </form>
            </div>
        </div>
    </div>

    <script>
    function getParentForUpdate(str) {
        if (str.length === 0) {
            document.getElementById("updateParentData").innerHTML = "";
            return;
        }

        const xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                document.getElementById("updateParentData").innerHTML = this.responseText;
            }
        };
        xhr.open("GET", "searchForUpdateParent.php?key=" + encodeURIComponent(str), true);
        xhr.send();
    }
    </script>
</body>
</html>
<?php
// Close database connection
if (isset($stmt)) $stmt->close();
if (isset($stmt_user)) $stmt_user->close();
if (isset($check_stmt)) $check_stmt->close();
if (isset($conn)) $conn->close();
?>
