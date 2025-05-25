<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Récupération des informations de l'étudiant
$student_info = db_fetch_row(
    "SELECT * FROM students WHERE id = ?",
    [$check],
    's'
);

if (!$student_info) {
    header("Location: ../../?error=student_not_found");
    exit();
}

// Préparer le contenu de la page
ob_start();
?>

<!-- Informations de l'étudiant -->
<div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <div class="p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Mes Informations</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Photo de profil -->
            <div class="flex flex-col items-center">
                <img src="../images/<?php echo htmlspecialchars($check) . ".jpg"; ?>" 
                     class="h-48 w-48 rounded-full object-cover border-4 border-gray-200"
                     alt="<?php echo htmlspecialchars($check) . " photo"; ?>"/>
            </div>
            
            <!-- Informations détaillées -->
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">ID Étudiant</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['id']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Nom</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['name']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Téléphone</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['phone']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Email</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['email']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Genre</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['sex']); ?></p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Date de naissance</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['dob']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Date d'admission</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['addmissiondate']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Adresse</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['address']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">ID Parent</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['parentid']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">ID Classe</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student_info['classid']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include_once('templates/layout.php');
?>

