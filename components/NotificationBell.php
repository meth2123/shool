<?php
require_once __DIR__ . '/../service/NotificationService.php';

class NotificationBell {
    private $notificationService;
    private $user_id;
    private $user_type;

    public function __construct($db, $user_id, $user_type) {
        $this->notificationService = new NotificationService($db, $user_id, $user_type);
        $this->user_id = $user_id;
        $this->user_type = $user_type;
    }
    
    public function getUnreadCount() {
        return $this->notificationService->countUnread();
    }
    
    public function getNotifications($limit = 5) {
        return $this->notificationService->getAll($limit, 0);
    }

    public function render() {
        $unreadCount = $this->notificationService->countUnread();
        $notifications = $this->notificationService->getUnread();
        
        // Générer un ID unique pour le menu déroulant
        $dropdownId = 'notification-dropdown-' . uniqid();
        
        $html = '
        <div class="relative inline-block text-left">
            <button type="button" 
                    class="relative p-2 text-gray-600 hover:text-gray-800 focus:outline-none"
                    onclick="document.getElementById(\'' . $dropdownId . '\').classList.toggle(\'hidden\')">
                <i class="fas fa-bell text-xl"></i>';
        
        if ($unreadCount > 0) {
            $html .= '
                <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full">
                    ' . $unreadCount . '
                </span>';
        }
        
        $html .= '
            </button>
            
            <div id="' . $dropdownId . '" 
                 class="hidden absolute right-0 mt-2 w-80 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                <div class="py-1" role="menu" aria-orientation="vertical">
                    <div class="px-4 py-2 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-sm font-medium text-gray-900">Notifications</h3>';
        
        if ($unreadCount > 0) {
            $html .= '
                <form action="mark_all_read.php" method="POST" class="inline">
                    <input type="hidden" name="user_type" value="' . htmlspecialchars($this->user_type) . '">
                    <button type="submit" class="text-xs text-blue-600 hover:text-blue-800">
                        Tout marquer comme lu
                    </button>
                </form>';
        }
        
        $html .= '
                        </div>
                    </div>';
        
        if (empty($notifications)) {
            $html .= '
                <div class="px-4 py-3 text-sm text-gray-500 text-center">
                    Aucune notification non lue
                </div>';
        } else {
            foreach ($notifications as $notification) {
                $typeClass = $this->getTypeClass($notification['type']);
                $html .= '
                <div class="px-4 py-3 hover:bg-gray-50 ' . $typeClass . '">
                    <div class="flex items-start">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">' . htmlspecialchars($notification['title']) . '</p>
                            <p class="mt-1 text-sm text-gray-500">' . htmlspecialchars($notification['message']) . '</p>
                            <p class="mt-1 text-xs text-gray-400">' . $this->formatDate($notification['created_at']) . '</p>';
                
                if ($notification['link']) {
                    $html .= '
                            <a href="' . htmlspecialchars($notification['link']) . '" 
                               class="mt-2 inline-flex items-center text-xs text-blue-600 hover:text-blue-800">
                                Voir plus <i class="fas fa-chevron-right ml-1"></i>
                            </a>';
                }
                
                $html .= '
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <form action="mark_read.php" method="POST" class="inline">
                                <input type="hidden" name="notification_id" value="' . $notification['id'] . '">
                                <input type="hidden" name="user_type" value="' . htmlspecialchars($this->user_type) . '">
                                <button type="submit" class="text-gray-400 hover:text-gray-500">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>';
            }
        }
        
        $html .= '
                </div>
            </div>
        </div>
        
        <script>
            // Fermer le menu déroulant si on clique en dehors
            document.addEventListener("click", function(event) {
                const dropdown = document.getElementById("' . $dropdownId . '");
                const button = event.target.closest("button");
                
                if (!dropdown.contains(event.target) && !button) {
                    dropdown.classList.add("hidden");
                }
            });
        </script>';
        
        return $html;
    }

    private function getTypeClass($type) {
        switch ($type) {
            case 'success':
                return 'border-l-4 border-green-500';
            case 'warning':
                return 'border-l-4 border-yellow-500';
            case 'error':
                return 'border-l-4 border-red-500';
            default:
                return 'border-l-4 border-blue-500';
        }
    }

    private function formatDate($date) {
        $timestamp = strtotime($date);
        $now = time();
        $diff = $now - $timestamp;
        
        if ($diff < 60) {
            return 'À l\'instant';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return 'Il y a ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return 'Il y a ' . $hours . ' heure' . ($hours > 1 ? 's' : '');
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return 'Il y a ' . $days . ' jour' . ($days > 1 ? 's' : '');
        } else {
            return date('d/m/Y H:i', $timestamp);
        }
    }
} 