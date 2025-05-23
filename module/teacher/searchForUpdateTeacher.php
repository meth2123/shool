<?php
include_once('main.php');
include_once('../../service/db_utils.php');

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

// Génération du formulaire avec style Tailwind CSS
?>
<form method="post" action="updateTeacher.php" class="space-y-6">
    <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- ID -->
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="id">ID</label>
                <input type="text" id="id" name="id" 
                    value="<?php echo htmlspecialchars($teacher['id']); ?>" 
                    readonly
                    class="bg-gray-100 shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <!-- Nom -->
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Nom</label>
                <input type="text" id="name" name="name" 
                    value="<?php echo htmlspecialchars($teacher['name']); ?>" 
                    readonly
                    class="bg-gray-100 shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <!-- Mot de passe -->
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Mot de passe</label>
                <input type="password" id="password" name="password" 
                    value="<?php echo htmlspecialchars($teacher['password']); ?>"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <!-- Téléphone -->
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">Téléphone</label>
                <input type="tel" id="phone" name="phone" 
                    value="<?php echo htmlspecialchars($teacher['phone']); ?>"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <!-- Email -->
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                <input type="email" id="email" name="email" 
                    value="<?php echo htmlspecialchars($teacher['email']); ?>"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <!-- Genre -->
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="gender">Genre</label>
                <input type="text" id="gender" name="gender" 
                    value="<?php echo htmlspecialchars($teacher['sex']); ?>" 
                    readonly
                    class="bg-gray-100 shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <!-- Date de naissance -->
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="dob">Date de naissance</label>
                <input type="text" id="dob" name="dob" 
                    value="<?php echo htmlspecialchars($teacher['dob']); ?>" 
                    readonly
                    class="bg-gray-100 shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <!-- Adresse -->
            <div class="md:col-span-2">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="address">Adresse</label>
                <input type="text" id="address" name="address" 
                    value="<?php echo htmlspecialchars($teacher['address']); ?>"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
        </div>

        <!-- Bouton de soumission -->
        <div class="flex items-center justify-end mt-6">
            <button type="submit" name="submit" 
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Mettre à jour
            </button>
        </div>
    </div>
</form>
