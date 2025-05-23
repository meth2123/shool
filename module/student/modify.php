<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Récupérer le mot de passe de l'étudiant
$student_info = db_fetch_row(
    "SELECT password FROM students WHERE id = ?",
    [$check],
    's'
);

if (!$student_info) {
    header("Location: index.php?error=student_not_found");
    exit();
}

$content = '
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold text-center text-gray-900 mb-6">Changer le mot de passe</h1>
        
        <form onsubmit="return modifyValidate();" action="modifysave.php" method="post" class="space-y-4">
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    Mot de passe actuel
                </label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       value="' . htmlspecialchars($student_info['password']) . '"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       readonly>
            </div>
            
            <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">
                    Nouveau mot de passe
                </label>
                <input type="password" 
                       id="new_password" 
                       name="new_password" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       required>
            </div>
            
            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                    Confirmer le nouveau mot de passe
                </label>
                <input type="password" 
                       id="confirm_password" 
                       name="confirm_password" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       required>
            </div>
            
            <div class="mt-6">
                <button type="submit" 
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-key mr-2"></i>
                    Changer le mot de passe
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function modifyValidate() {
    const newPassword = document.getElementById("new_password").value;
    const confirmPassword = document.getElementById("confirm_password").value;
    
    if (newPassword.length < 6) {
        alert("Le mot de passe doit contenir au moins 6 caractères");
        return false;
    }
    
    if (newPassword !== confirmPassword) {
        alert("Les mots de passe ne correspondent pas");
        return false;
    }
    
    return true;
}
</script>';

include('templates/layout.php');
?>

