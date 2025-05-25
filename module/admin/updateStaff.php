<?php
include_once('main.php');
include_once('includes/admin_actions.php');
include_once('includes/admin_utils.php');

$admin_id = $_SESSION['login_id'];
$staff_id = $_GET['id'] ?? '';

if (empty($staff_id)) {
    header("Location: manageStaff.php?error=" . urlencode("ID du personnel non spécifié"));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $sex = $_POST['sex'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $salary = $_POST['salary'] ?? '';
    $password = $_POST['password'] ?? '';

    // Verify that the staff member belongs to this admin
    $check_sql = "SELECT id FROM staff WHERE id = ? AND created_by = ?";
    $check_stmt = $link->prepare($check_sql);
    $check_stmt->bind_param("ss", $staff_id, $admin_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        header("Location: manageStaff.php?error=" . urlencode("Personnel non trouvé ou accès non autorisé"));
        exit;
    }

    // Start transaction
    $link->begin_transaction();

    try {
        // Update staff information
        $update_sql = "UPDATE staff SET 
            name = ?, 
            email = ?, 
            phone = ?, 
            address = ?, 
            sex = ?, 
            dob = ?, 
            salary = ? 
            WHERE id = ? AND created_by = ?";
        
        $update_stmt = $link->prepare($update_sql);
        $update_stmt->bind_param("ssssssdss", 
            $name, $email, $phone, $address, $sex, $dob, $salary, $staff_id, $admin_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Erreur lors de la mise à jour des informations");
        }

        // Update password if provided
        if (!empty($password)) {
            $update_pass_sql = "UPDATE users SET password = ? WHERE userid = ?";
            $update_pass_stmt = $link->prepare($update_pass_sql);
            $update_pass_stmt->bind_param("ss", $password, $staff_id);
            
            if (!$update_pass_stmt->execute()) {
                throw new Exception("Erreur lors de la mise à jour du mot de passe");
            }
        }

        $link->commit();
        header("Location: viewStaff.php?id=" . urlencode($staff_id) . "&success=1");
        exit;
    } catch (Exception $e) {
        $link->rollback();
        $error = $e->getMessage();
    }
}

// Get staff details
$sql = "SELECT s.*, u.password 
        FROM staff s 
        LEFT JOIN users u ON s.id = u.userid 
        WHERE s.id = ? AND s.created_by = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("ss", $staff_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();

if (!$staff) {
    header("Location: manageStaff.php?error=" . urlencode("Personnel non trouvé ou accès non autorisé"));
    exit;
}

$content = '
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Modifier le Personnel</h2>
                <a href="viewStaff.php?id=' . htmlspecialchars($staff_id) . '" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Retour aux détails
                </a>
            </div>

            ' . (isset($error) ? '<div class="alert alert-danger mb-4">' . htmlspecialchars($error) . '</div>' : '') . '

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Informations Personnelles</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nom</label>
                                <input type="text" name="name" id="name" required
                                       value="' . htmlspecialchars($staff['name']) . '"
                                       class="form-control">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" id="email" required
                                       value="' . htmlspecialchars($staff['email']) . '"
                                       class="form-control">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Téléphone</label>
                                <input type="tel" name="phone" id="phone" required
                                       value="' . htmlspecialchars($staff['phone']) . '"
                                       class="form-control">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="address" class="form-label">Adresse</label>
                                <input type="text" name="address" id="address" required
                                       value="' . htmlspecialchars($staff['address']) . '"
                                       class="form-control">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="sex" class="form-label">Genre</label>
                                <select name="sex" id="sex" required class="form-select">
                                    <option value="M" ' . ($staff['sex'] === 'M' ? 'selected' : '') . '>Masculin</option>
                                    <option value="F" ' . ($staff['sex'] === 'F' ? 'selected' : '') . '>Féminin</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="dob" class="form-label">Date de naissance</label>
                                <input type="date" name="dob" id="dob" required
                                       value="' . htmlspecialchars($staff['dob']) . '"
                                       class="form-control">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="salary" class="form-label">Salaire</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-euro-sign"></i></span>
                                    <input type="number" name="salary" id="salary" required step="0.01"
                                           value="' . htmlspecialchars($staff['salary']) . '"
                                           class="form-control">
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">
                                    Nouveau mot de passe (laisser vide pour ne pas changer)
                                </label>
                                <input type="password" name="password" id="password" class="form-control">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>';

include('templates/layout.php');
?>
