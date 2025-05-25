<?php
include_once('main.php');
include_once('includes/auth_check.php');
require_once('../../db/config.php');
include_once('../../service/db_utils.php');

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// L'ID de l'administrateur est déjà défini dans auth_check.php
// $admin_id = $_SESSION['login_id'];

// La vérification de l'authentification est déjà faite dans auth_check.php

// Fonction pour obtenir les classes
function getClasses() {
    global $admin_id;
    return db_fetch_all("
        SELECT DISTINCT c.* 
        FROM class c 
        INNER JOIN students s ON c.id = s.classid 
        WHERE s.created_by = ? 
        ORDER BY c.name", 
        [$admin_id], 
        's'
    );
}

// Fonction pour obtenir les montants des paiements par classe
function getClassPaymentAmounts() {
    global $admin_id;
    return db_fetch_all("
        SELECT cpa.*, c.name as class_name 
        FROM class_payment_amount cpa 
        JOIN class c ON cpa.class_id = c.id 
        JOIN students s ON c.id = s.classid 
        WHERE s.created_by = ? 
        GROUP BY cpa.id, c.name 
        ORDER BY c.name",
        [$admin_id],
        's'
    );
}

// Fonction pour obtenir l'historique des paiements
function getPaymentHistory($filters = []) {
    global $admin_id;
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
        WHERE s.created_by = ?
    ";
    $params = [$admin_id];
    $types = 's';

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

// Récupérer les paiements du mois en cours
$conn = getDbConnection();
$sql = "SELECT p.*, s.name as student_name 
        FROM payment p 
        INNER JOIN students s ON p.studentid = s.id 
        WHERE p.created_by = ?
        ORDER BY p.id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

$content = '
<div class="container py-4">
    <!-- Section 1: Paiements du Mois en Cours -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h4 mb-4">
                <i class="fas fa-money-bill-wave me-2 text-primary"></i>
                Paiements du Mois en Cours
            </h2>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Étudiant</th>
                            <th>Montant</th>
                            <th>Mois</th>
                            <th>Année</th>
                        </tr>
                    </thead>
                    <tbody>';
                    
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $content .= '
                            <tr>
                                <td>' . htmlspecialchars($row['id']) . '</td>
                                <td>' . htmlspecialchars($row['student_name']) . ' (' . htmlspecialchars($row['studentid']) . ')</td>
                                <td>' . number_format($row['amount'], 2) . ' €</td>
                                <td>' . htmlspecialchars($row['month']) . '</td>
                                <td>' . htmlspecialchars($row['year']) . '</td>
                            </tr>';
                        }
                    } else {
                        $content .= '
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Aucun paiement trouvé pour ce mois
                            </td>
                        </tr>';
                    }
                    
                    $content .= '
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section 2: Configuration des montants des paiements -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h4 mb-4">Configuration des montants des paiements</h2>
            
            <!-- Formulaire pour définir les montants -->
            <form method="POST" class="mb-4">
                <input type="hidden" name="action" value="set_payment_amount">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="class_id" class="form-label">Classe</label>
                        <select id="class_id" name="class_id" required class="form-select">
                            <option value="">Sélectionner une classe</option>';
                            
                            foreach ($classes as $class) {
                                $content .= '<option value="' . htmlspecialchars($class['id']) . '">' . 
                                          htmlspecialchars($class['name']) . '</option>';
                            }
                            
                            $content .= '
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="amount" class="form-label">Montant mensuel (€)</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0" required class="form-control">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Enregistrer
                        </button>
                    </div>
                </div>
            </form>

            <!-- Tableau des montants actuels -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Classe</th>
                            <th>Montant mensuel</th>
                            <th>Dernière mise à jour</th>
                        </tr>
                    </thead>
                    <tbody>';
                    
                    foreach ($paymentAmounts as $amount) {
                        $content .= '
                        <tr>
                            <td>' . htmlspecialchars($amount['class_name']) . '</td>
                            <td>' . number_format($amount['amount'], 2) . ' €</td>
                            <td>' . date('d/m/Y H:i', strtotime($amount['updated_at'])) . '</td>
                        </tr>';
                    }
                    
                    if (empty($paymentAmounts)) {
                        $content .= '
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">
                                Aucun montant configuré
                            </td>
                        </tr>';
                    }
                    
                    $content .= '
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section 3: Historique des paiements -->
    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h4 mb-4">Historique des paiements</h2>

            <!-- Filtres -->
            <form method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="class_id_filter" class="form-label">Classe</label>
                        <select id="class_id_filter" name="class_id" class="form-select">
                            <option value="">Toutes les classes</option>';
                            
                            foreach ($classes as $class) {
                                $content .= '<option value="' . htmlspecialchars($class['id']) . '"' . 
                                          (isset($_GET['class_id']) && $_GET['class_id'] === $class['id'] ? ' selected' : '') . '>' . 
                                          htmlspecialchars($class['name']) . '</option>';
                            }
                            
                            $content .= '
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="year_filter" class="form-label">Année</label>
                        <select id="year_filter" name="year" class="form-select">
                            <option value="">Toutes les années</option>';
                            
                            $currentYear = date('Y');
                            for ($year = $currentYear; $year >= $currentYear - 2; $year--) {
                                $content .= '<option value="' . $year . '"' . 
                                         (isset($_GET['year']) && $_GET['year'] == $year ? ' selected' : '') . '>' . 
                                         $year . '</option>';
                            }
                            
                            $content .= '
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="month_filter" class="form-label">Mois</label>
                        <select id="month_filter" name="month" class="form-select">
                            <option value="">Tous les mois</option>';
                            
                            $months = [
                                10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
                                1 => 'Janvier', 2 => 'Février', 3 => 'Mars',
                                4 => 'Avril', 5 => 'Mai', 6 => 'Juin'
                            ];
                            foreach ($months as $num => $name) {
                                $content .= '<option value="' . $num . '"' . 
                                         (isset($_GET['month']) && $_GET['month'] == $num ? ' selected' : '') . '>' . 
                                         $name . '</option>';
                            }
                            
                            $content .= '
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i>Filtrer
                        </button>
                    </div>
                </div>
            </form>

            <!-- Tableau des paiements -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Étudiant</th>
                            <th>Classe</th>
                            <th>Mois</th>
                            <th>Année</th>
                            <th>Montant</th>
                            <th>Source</th>
                            <th>Effectué par</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>';
                    
                    if (!empty($paymentHistory)) {
                        foreach ($paymentHistory as $payment) {
                            $badgeClass = $payment['payment_source'] === 'Administration' 
                                ? 'bg-primary' 
                                : ($payment['payment_source'] === 'Parent' 
                                    ? 'bg-success' 
                                    : 'bg-secondary');
                                    
                            $content .= '
                            <tr>
                                <td>' . htmlspecialchars($payment['student_name']) . '</td>
                                <td>' . htmlspecialchars($payment['class_name']) . '</td>
                                <td>';
                                
                                $months = [
                                    10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
                                    1 => 'Janvier', 2 => 'Février', 3 => 'Mars',
                                    4 => 'Avril', 5 => 'Mai', 6 => 'Juin'
                                ];
                                $content .= $months[$payment['month']] ?? $payment['month'];
                                
                                $content .= '</td>
                                <td>' . $payment['year'] . '</td>
                                <td>' . number_format($payment['amount'], 2) . ' €</td>
                                <td><span class="badge ' . $badgeClass . '">' . htmlspecialchars($payment['payment_source']) . '</span></td>
                                <td>';
                                
                                if ($payment['payment_source'] === 'Administration' && $payment['admin_name']) {
                                    $content .= htmlspecialchars($payment['admin_name']);
                                } else {
                                    $content .= '-';
                                }
                                
                                $content .= '</td>
                                <td>';
                                
                                if (isset($payment['created_at'])) {
                                    $content .= date('d/m/Y H:i', strtotime($payment['created_at']));
                                } else {
                                    $content .= date('d/m/Y', strtotime($payment['year'] . '-' . $payment['month'] . '-01'));
                                }
                                
                                $content .= '</td>
                            </tr>';
                        }
                    } else {
                        $content .= '
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">
                                Aucun paiement trouvé avec les filtres sélectionnés
                            </td>
                        </tr>';
                    }
                    
                    $content .= '
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Actions rapides -->
    <div class="d-flex justify-content-center mt-4">
        <a href="addPayment.php" class="btn btn-primary me-2">
            <i class="fas fa-plus-circle me-2"></i>Ajouter un Paiement
        </a>
        <a href="deletePayment.php" class="btn btn-danger">
            <i class="fas fa-trash me-2"></i>Supprimer un Paiement
        </a>
    </div>
</div>';

// Fermer la connexion à la base de données
$stmt->close();
$conn->close();

include('templates/layout.php');
?>
