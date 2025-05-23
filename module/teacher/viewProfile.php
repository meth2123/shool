<?php
include_once('main.php');
include_once('../../service/db_utils.php');

// Vérification de la variable $check
if (!isset($check) || empty($check)) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Erreur!</strong>
            <span class="block sm:inline">ID enseignant non valide.</span>
          </div>';
    exit();
}

// Récupération des informations de l'enseignant
$teacher = db_fetch_row(
    "SELECT * FROM teachers WHERE id = ?",
    [$check],
    's'
);

if (!$teacher) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Erreur!</strong>
            <span class="block sm:inline">Enseignant non trouvé.</span>
          </div>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Profil Enseignant</title>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Photo de profil -->
            <div class="w-full flex justify-center p-8 bg-gray-50">
                <img src="../images/<?php echo htmlspecialchars($check); ?>.jpg" 
                     alt="Photo de <?php echo htmlspecialchars($teacher['name']); ?>"
                     class="w-48 h-48 rounded-full object-cover border-4 border-white shadow-lg">
            </div>

            <!-- Informations du profil -->
            <div class="p-8">
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-8">
                    <?php echo htmlspecialchars($teacher['name']); ?>
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-gray-600">ID</p>
                            <p class="font-medium"><?php echo htmlspecialchars($teacher['id']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Téléphone</p>
                            <p class="font-medium"><?php echo htmlspecialchars($teacher['phone']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Email</p>
                            <p class="font-medium"><?php echo htmlspecialchars($teacher['email']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Genre</p>
                            <p class="font-medium"><?php echo htmlspecialchars($teacher['sex']); ?></p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-gray-600">Date de naissance</p>
                            <p class="font-medium"><?php echo htmlspecialchars($teacher['dob']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Date d'embauche</p>
                            <p class="font-medium"><?php echo htmlspecialchars($teacher['hiredate']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Salaire</p>
                            <p class="font-medium"><?php echo htmlspecialchars($teacher['salary']); ?> €</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>