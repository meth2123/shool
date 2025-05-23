<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Récupération des informations du parent
$parent_info = db_fetch_row(
    "SELECT * FROM parents WHERE id = ?",
    [$check],
    's'
);

if (!$parent_info) {
    header("Location: ../../?error=parent_not_found");
    exit();
}

// Récupération des enfants du parent
$children = db_fetch_all(
    "SELECT s.*, c.name as class_name 
     FROM students s
     LEFT JOIN class c ON s.classid = c.id
     WHERE s.parentid = ?",
    [$check],
    's'
);

// Si la requête échoue, initialiser un tableau vide
if (!$children) {
    $children = [];
}

// Fonction pour obtenir les mois de l'année scolaire (sans les vacances)
function getSchoolMonths() {
    // Si nous sommes en juillet ou août, retourner les mois de la nouvelle année scolaire
    $currentMonth = date('n');
    if ($currentMonth >= 7) {
        return [
            '9' => 'Septembre',
            '10' => 'Octobre',
            '11' => 'Novembre',
            '12' => 'Décembre',
            '1' => 'Janvier',
            '2' => 'Février',
            '3' => 'Mars',
            '4' => 'Avril',
            '5' => 'Mai',
            '6' => 'Juin'
        ];
    }
    
    // Sinon, retourner les mois de l'année scolaire en cours
    return [
        '10' => 'Octobre',
        '11' => 'Novembre',
        '12' => 'Décembre',
        '1' => 'Janvier',
        '2' => 'Février',
        '3' => 'Mars',
        '4' => 'Avril',
        '5' => 'Mai',
        '6' => 'Juin'
    ];
}

// Fonction pour obtenir l'année scolaire en cours
function getCurrentSchoolYear() {
    $currentMonth = date('n');
    $currentYear = date('Y');
    
    // Log pour débogage
    error_log("=== Calcul de l'année scolaire ===");
    error_log("Mois actuel: " . $currentMonth . ", Année actuelle: " . $currentYear);
    
    // Pour l'année scolaire 2024-2025, nous retournons toujours 2024
    // car c'est l'année de référence pour cette année scolaire
    $schoolYear = 2024;
    
    error_log("Année scolaire calculée: " . $schoolYear);
    return $schoolYear;
}

// Fonction pour obtenir le montant mensuel d'une classe
function getClassMonthlyAmount($classId) {
    $amount = db_fetch_row(
        "SELECT amount FROM class_payment_amount WHERE class_id = ?",
        [$classId],
        's'
    );
    
    // Si aucun montant n'est défini, utiliser le montant par défaut en FCFA (10,000 FCFA)
    return $amount ? $amount['amount'] : 10000;
}

// Fonction pour formater le montant en FCFA
function formatAmount($amount) {
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

// Fonction pour obtenir les paiements d'un étudiant
function getStudentPayments($studentId) {
    $schoolYear = getCurrentSchoolYear();
    
    // Log pour débogage
    error_log("=== Début de la recherche des paiements ===");
    error_log("ID Étudiant: " . $studentId);
    error_log("Année scolaire recherchée: " . $schoolYear);
    
    // Vérifier d'abord si l'étudiant existe
    $student = db_fetch_row(
        "SELECT id, name FROM students WHERE id = ?",
        [$studentId],
        's'
    );
    
    if (!$student) {
        error_log("ERREUR: Étudiant non trouvé - ID: " . $studentId);
        return [
            'payments' => [],
            'paidMonths' => [],
            'totalPaid' => 0
        ];
    }
    
    error_log("Étudiant trouvé: " . $student['name'] . " (ID: " . $student['id'] . ")");
    
    // Récupérer tous les paiements pour l'année scolaire 2024-2025 (année 2025)
    $query = "SELECT p.*, 
                     CASE 
                         WHEN p.created_by LIKE 'ad-%' THEN 'Administration'
                         WHEN p.created_by LIKE 'pa-%' THEN 'Parent'
                         ELSE 'Autre'
                     END as payment_source,
                     a.name as admin_name
              FROM payment p
              LEFT JOIN admin a ON p.created_by = a.id
              WHERE p.studentid = ? AND p.year = 2025
              ORDER BY p.month ASC";
              
    error_log("Requête SQL: " . $query);
    error_log("Paramètres: studentId = " . $studentId);
    
    $payments = db_fetch_all($query, [$studentId], 's');
    
    // Log détaillé des paiements trouvés
    if ($payments) {
        error_log("Nombre de paiements trouvés: " . count($payments));
        foreach ($payments as $payment) {
            error_log(sprintf(
                "Paiement - ID: %s, Étudiant: %s, Montant: %s, Mois: %s, Année: %s, Créé par: %s",
                $payment['id'],
                $payment['studentid'],
                $payment['amount'],
                $payment['month'],
                $payment['year'],
                $payment['created_by'] ?? 'NULL'
            ));
        }
    } else {
        error_log("Aucun paiement trouvé pour l'étudiant");
    }
    
    $paidMonths = [];
    $totalPaid = 0;
    foreach ($payments as $payment) {
        $paidMonths[$payment['month']] = [
            'amount' => $payment['amount'],
            'source' => $payment['payment_source'],
            'admin_name' => $payment['admin_name'] ?? null,
            'created_at' => $payment['created_at'] ?? null
        ];
        $totalPaid += $payment['amount'];
    }
    
    error_log("=== Fin de la recherche des paiements ===");
    error_log("Total payé: " . $totalPaid);
    error_log("Nombre de mois payés: " . count($paidMonths));
    
    return [
        'payments' => $payments,
        'paidMonths' => $paidMonths,
        'totalPaid' => $totalPaid
    ];
}

// Fonction pour calculer les mois impayés
function getUnpaidMonths($paidMonths) {
    $schoolMonths = getSchoolMonths();
    $currentMonth = date('n');
    $currentYear = date('Y');
    $schoolYear = getCurrentSchoolYear();
    $unpaidMonths = [];
    
    // Log pour débogage
    error_log("=== Calcul des mois impayés ===");
    error_log("Mois actuel: " . $currentMonth);
    error_log("Année actuelle: " . $currentYear);
    error_log("Année scolaire: " . $schoolYear);
    
    // Pour l'année scolaire 2024-2025, nous affichons tous les mois jusqu'à juin
    $lastMonthToShow = 6;
    error_log("Dernier mois à afficher: " . $lastMonthToShow);
    
    foreach ($schoolMonths as $monthNum => $monthName) {
        // Log pour chaque mois
        error_log("Vérification du mois: " . $monthName . " (" . $monthNum . ")");
        
        // Ne pas afficher les mois après juin
        if ($monthNum > $lastMonthToShow) {
            error_log("Mois " . $monthName . " ignoré car après juin");
            continue;
        }
        
        if (!isset($paidMonths[$monthNum])) {
            error_log("Mois " . $monthName . " ajouté aux mois impayés");
            $unpaidMonths[$monthNum] = $monthName;
        } else {
            error_log("Mois " . $monthName . " déjà payé");
        }
    }
    
    // Trier les mois impayés par ordre chronologique
    ksort($unpaidMonths);
    
    error_log("Mois impayés trouvés: " . implode(", ", $unpaidMonths));
    return $unpaidMonths;
}

// Fonction pour sécuriser l'affichage des chaînes
function safe_html($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiements des Enfants - Système de Gestion Scolaire</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <img src="../../source/logo.jpg" class="h-12 w-12 object-contain" alt="School Management System"/>
                    <h1 class="ml-4 text-xl font-semibold text-gray-800">Système de Gestion Scolaire</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Bonjour, <?php echo safe_html($parent_info['fathername']); ?></span>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Menu de navigation -->
    <div class="bg-white shadow-md mt-6 mx-4 lg:mx-auto max-w-7xl rounded-lg">
        <div class="flex flex-wrap justify-center gap-4 p-4">
            <a href="index.php" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-home mr-2"></i>Accueil
            </a>
            <a href="modify.php" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-key mr-2"></i>Changer le mot de passe
            </a>
            <a href="checkchild.php" class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-child mr-2"></i>Information enfant
            </a>
            <a href="childpayment.php" class="bg-indigo-500 hover:bg-indigo-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-money-bill-wave mr-2"></i>Paiements
            </a>
            <a href="childattendance.php" class="bg-pink-500 hover:bg-pink-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-calendar-check mr-2"></i>Présences
            </a>
            <a href="childreport.php" class="bg-teal-500 hover:bg-teal-600 text-white px-6 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out">
                <i class="fas fa-file-alt mr-2"></i>Bulletins
            </a>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <?php if (empty($children)): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                <p>Aucun enfant n'est associé à votre compte.</p>
            </div>
        <?php else: ?>
            <?php foreach ($children as $child): 
                $paymentInfo = getStudentPayments($child['id']);
                $unpaidMonths = getUnpaidMonths($paymentInfo['paidMonths']);
                $monthlyFee = getClassMonthlyAmount($child['classid']); // Utiliser le montant défini pour la classe
                $totalDue = count(getSchoolMonths()) * $monthlyFee;
                $totalPaid = $paymentInfo['totalPaid'];
                $remainingAmount = $totalDue - $totalPaid;
                $schoolYear = getCurrentSchoolYear();
            ?>
                <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800"><?php echo safe_html($child['name']); ?></h2>
                                <p class="text-gray-600">Classe: <?php echo safe_html($child['class_name']); ?></p>
                                <p class="text-sm text-gray-500">Montant mensuel: <?php echo formatAmount($monthlyFee); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-600">Année scolaire: <?php echo safe_html($schoolYear . '-' . ($schoolYear + 1)); ?></p>
                                <p class="text-lg font-semibold <?php echo $remainingAmount > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                    <?php echo $remainingAmount > 0 ? 'Reste à payer: ' . formatAmount($remainingAmount) : 'Tout est payé'; ?>
                                </p>
                            </div>
                        </div>

                        <!-- Résumé des paiements -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h3 class="text-lg font-semibold text-blue-800 mb-2">Total payé</h3>
                                <p class="text-2xl font-bold text-blue-600"><?php echo formatAmount($totalPaid); ?></p>
                                <p class="text-sm text-blue-600 mt-1">Sur <?php echo count(getSchoolMonths()); ?> mois (hors vacances)</p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <h3 class="text-lg font-semibold text-green-800 mb-2">Total dû</h3>
                                <p class="text-2xl font-bold text-green-600"><?php echo formatAmount($totalDue); ?></p>
                                <p class="text-sm text-green-600 mt-1"><?php echo count(getSchoolMonths()); ?> x <?php echo formatAmount($monthlyFee); ?></p>
                            </div>
                            <div class="bg-red-50 p-4 rounded-lg">
                                <h3 class="text-lg font-semibold text-red-800 mb-2">Reste à payer</h3>
                                <p class="text-2xl font-bold text-red-600"><?php echo formatAmount($remainingAmount); ?></p>
                                <p class="text-sm text-red-600 mt-1"><?php echo count($unpaidMonths); ?> mois restants</p>
                    </div>
                </div>

                        <!-- Paiements effectués -->
                        <div class="mb-8">
                            <h3 class="text-xl font-semibold text-gray-800 mb-4">Paiements effectués</h3>
                            <?php 
                                // Log pour débogage de l'affichage
                                error_log("Affichage des paiements pour l'étudiant: " . $child['id']);
                                error_log("Année scolaire: " . $schoolYear);
                                error_log("Nombre de paiements: " . count($paymentInfo['payments']));
                            ?>
                            <?php if (empty($paymentInfo['payments'])): ?>
                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-yellow-700">
                                                Aucun paiement effectué pour l'année scolaire <?php echo $schoolYear . '-' . ($schoolYear + 1); ?>.
                                                <?php if ($child['id']): ?>
                                                    <br>
                                                    <small>ID Étudiant: <?php echo safe_html($child['id']); ?></small>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mois</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date de paiement</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Effectué par</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Année</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($paymentInfo['payments'] as $payment): ?>
                            <tr class="hover:bg-gray-50">
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <?php echo getSchoolMonths()[$payment['month']]; ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <?php echo formatAmount($payment['amount']); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php 
                                                            if (isset($payment['created_at'])) {
                                                                echo date('d/m/Y', strtotime($payment['created_at']));
                                                            } else {
                                                                echo date('d/m/Y', strtotime($payment['year'] . '-' . $payment['month'] . '-01'));
                                                            }
                                                        ?>
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
                                                            <?php echo safe_html($payment['payment_source']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <?php 
                                                            if ($payment['payment_source'] === 'Administration' && $payment['admin_name']) {
                                                                echo safe_html($payment['admin_name']);
                                                            } else {
                                                                echo '-';
                                                            }
                                                        ?>
                                </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <?php echo safe_html($payment['year']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                        </div>

                        <!-- Mois impayés -->
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800 mb-4">Mois impayés</h3>
                            <?php if (empty($unpaidMonths)): ?>
                                <p class="text-green-600">Tous les mois sont payés !</p>
                            <?php else: ?>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <?php foreach ($unpaidMonths as $monthNum => $monthName): ?>
                                        <div class="bg-red-50 p-4 rounded-lg">
                                            <p class="text-lg font-semibold text-red-800"><?php echo $monthName; ?></p>
                                            <p class="text-red-600"><?php echo formatAmount($monthlyFee); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
            </div>
        </div>
            <?php endforeach; ?>
        <?php endif; ?>
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