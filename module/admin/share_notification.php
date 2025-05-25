<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../service/NotificationService.php';
require_once __DIR__ . '/../../service/AuthService.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
$authService = new AuthService($db);
if (!$authService->isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    header('Location: /gestion/login.php');
    exit;
}

// Récupérer les paramètres de l'URL
$title = $_GET['title'] ?? '';
$message = $_GET['message'] ?? '';
$type = $_GET['type'] ?? 'info';
$target_type = $_GET['target_type'] ?? '';
$target_ids = isset($_GET['target_ids']) ? explode(',', $_GET['target_ids']) : [];
$link = $_GET['link'] ?? '';

// Valider le type de notification
$valid_types = ['info', 'success', 'warning', 'error'];
if (!in_array($type, $valid_types)) {
    $type = 'info';
}

// Valider le type de destinataire
$valid_target_types = ['teacher', 'student'];
if (!in_array($target_type, $valid_target_types)) {
    $target_type = '';
}

// Récupérer les utilisateurs pour le formulaire
$users = [
    'teacher' => [],
    'student' => []
];

// Récupérer les enseignants
$stmt = $db->prepare("SELECT id, name FROM teachers ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $users['teacher'][] = $row;
}

// Récupérer les élèves
$stmt = $db->prepare("SELECT id, name FROM students ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $users['student'][] = $row;
}

// Inclure l'en-tête
$page_title = "Partager une Notification";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Partager une Notification</h1>
            <button onclick="copyShareLink()" 
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-copy mr-2"></i>
                Copier le lien de partage
            </button>
        </div>
        
        <!-- Formulaire d'ajout de notification -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Envoyer une nouvelle notification</h2>
            <form action="manage_notifications.php" method="POST" class="space-y-4" id="notificationForm">
                <input type="hidden" name="action" value="create">
                
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Titre</label>
                        <input type="text" name="title" id="title" required value="<?php echo htmlspecialchars($title); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                        <select name="type" id="type" required onchange="updateShareLink()"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="info" <?php echo $type === 'info' ? 'selected' : ''; ?>>Information</option>
                            <option value="success" <?php echo $type === 'success' ? 'selected' : ''; ?>>Succès</option>
                            <option value="warning" <?php echo $type === 'warning' ? 'selected' : ''; ?>>Avertissement</option>
                            <option value="error" <?php echo $type === 'error' ? 'selected' : ''; ?>>Erreur</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700">Message</label>
                    <textarea name="message" id="message" rows="3" required onchange="updateShareLink()"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"><?php echo htmlspecialchars($message); ?></textarea>
                </div>
                
                <div>
                    <label for="link" class="block text-sm font-medium text-gray-700">Lien (optionnel)</label>
                    <input type="text" name="link" id="link" value="<?php echo htmlspecialchars($link); ?>" onchange="updateShareLink()"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="target_type" class="block text-sm font-medium text-gray-700">Destinataires</label>
                        <select name="target_type" id="target_type" required onchange="updateTargetList(); updateShareLink()"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Sélectionner un type</option>
                            <option value="teacher" <?php echo $target_type === 'teacher' ? 'selected' : ''; ?>>Enseignants</option>
                            <option value="student" <?php echo $target_type === 'student' ? 'selected' : ''; ?>>Élèves</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="target_ids" class="block text-sm font-medium text-gray-700">Sélectionner les destinataires</label>
                        <select name="target_ids[]" id="target_ids" multiple onchange="updateShareLink()"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Chargement...</option>
                        </select>
                        <p class="mt-1 text-sm text-gray-500">Laissez vide pour envoyer à tous les utilisateurs du type sélectionné</p>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        Envoyer la notification
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Données des utilisateurs pour le formulaire
const users = <?php echo json_encode($users); ?>;
const initialTargetIds = <?php echo json_encode($target_ids); ?>;

function updateTargetList() {
    const targetType = document.getElementById('target_type').value;
    const targetSelect = document.getElementById('target_ids');
    
    // Vider la liste
    targetSelect.innerHTML = '';
    
    if (targetType && users[targetType]) {
        users[targetType].forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = user.name;
            // Sélectionner les IDs initiaux si présents
            if (initialTargetIds.includes(user.id.toString())) {
                option.selected = true;
            }
            targetSelect.appendChild(option);
        });
    }
}

function updateShareLink() {
    const title = encodeURIComponent(document.getElementById('title').value);
    const message = encodeURIComponent(document.getElementById('message').value);
    const type = document.getElementById('type').value;
    const targetType = document.getElementById('target_type').value;
    const link = encodeURIComponent(document.getElementById('link').value);
    
    const targetIdsSelect = document.getElementById('target_ids');
    const selectedOptions = Array.from(targetIdsSelect.selectedOptions).map(option => option.value);
    const targetIds = selectedOptions.join(',');
    
    const shareUrl = `${window.location.origin}/gestion/module/admin/share_notification.php?` +
                    `title=${title}&message=${message}&type=${type}&target_type=${targetType}` +
                    `&link=${link}&target_ids=${targetIds}`;
    
    // Stocker l'URL dans un attribut data pour le bouton de copie
    document.getElementById('notificationForm').setAttribute('data-share-url', shareUrl);
}

function copyShareLink() {
    const shareUrl = document.getElementById('notificationForm').getAttribute('data-share-url');
    if (shareUrl) {
        navigator.clipboard.writeText(shareUrl).then(() => {
            alert('Lien de partage copié dans le presse-papiers !');
        }).catch(err => {
            console.error('Erreur lors de la copie :', err);
            alert('Erreur lors de la copie du lien');
        });
    } else {
        alert('Veuillez remplir le formulaire pour générer un lien de partage');
    }
}

// Initialiser la liste des destinataires au chargement
document.addEventListener('DOMContentLoaded', () => {
    updateTargetList();
    updateShareLink();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 