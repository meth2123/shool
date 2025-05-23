<?php
include_once('main.php');
include_once('includes/admin_actions.php');

// Récupérer l'historique des actions
$result = getAdminActionHistory($link);

$content = <<<HTML
<div class="container mx-auto px-4">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Historique des actions administrateurs</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Table</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Affecté</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Détails</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
HTML;

while ($row = $result->fetch_assoc()) {
    $action_class = '';
    switch ($row['action_type']) {
        case 'CREATE':
            $action_class = 'bg-green-100 text-green-800';
            break;
        case 'UPDATE':
            $action_class = 'bg-yellow-100 text-yellow-800';
            break;
        case 'DELETE':
            $action_class = 'bg-red-100 text-red-800';
            break;
    }
    
    $details = json_decode($row['action_details'], true);
    $formatted_details = '';
    if ($details) {
        $formatted_details = '<ul class="list-disc list-inside">';
        foreach ($details as $key => $value) {
            if (is_array($value)) {
                $formatted_details .= "<li><strong>$key</strong>: " . json_encode($value) . "</li>";
            } else {
                $formatted_details .= "<li><strong>$key</strong>: $value</li>";
            }
        }
        $formatted_details .= '</ul>';
    }
    
    $content .= <<<HTML
    <tr>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            {$row['action_date']}
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm font-medium text-gray-900">{$row['admin_name']}</div>
            <div class="text-sm text-gray-500">{$row['admin_id']}</div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {$action_class}">
                {$row['action_type']}
            </span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            {$row['affected_table']}
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            {$row['affected_id']}
        </td>
        <td class="px-6 py-4 text-sm text-gray-500">
            {$formatted_details}
        </td>
    </tr>
HTML;
}

$content .= <<<HTML
                </tbody>
            </table>
        </div>
    </div>
</div>
HTML;

include('templates/layout.php');
?> 