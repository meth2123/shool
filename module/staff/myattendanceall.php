<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Récupération de toutes les présences
$attendances = db_fetch_all(
    "SELECT DISTINCT DATE_FORMAT(date, '%d/%m/%Y') as formatted_date 
     FROM attendance 
     WHERE attendedid = ?
     ORDER BY date DESC",
    [$check],
    'i'
);

if (empty($attendances)) {
    echo '<div class="p-4 text-center text-gray-500">Aucune présence enregistrée</div>';
} else {
    echo '<div class="overflow-hidden rounded-lg shadow">';
    echo '<table class="min-w-full divide-y divide-gray-200">';
    echo '<thead class="bg-gray-50">';
    echo '<tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date de présence</th></tr>';
    echo '</thead>';
    echo '<tbody class="bg-white divide-y divide-gray-200">';
    
    foreach ($attendances as $attendance) {
        echo '<tr class="hover:bg-gray-50">';
        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($attendance['formatted_date']) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}
?>
