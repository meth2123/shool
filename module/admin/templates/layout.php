<?php
// Assurons-nous que la variable $login_session est disponible
if (!isset($login_session) && isset($_SESSION['login_id'])) {
    // Si $login_session n'est pas défini mais que l'utilisateur est connecté, récupérons son nom
    require_once(__DIR__ . '/../../../service/mysqlcon.php');
    $stmt = $link->prepare("SELECT name FROM admin WHERE id = ?");
    $stmt->bind_param("s", $_SESSION['login_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $login_session = $row['name'] ?? 'Administrateur';
}

// Définir la variable $loged_user_name à partir de $login_session
$loged_user_name = $login_session ?? 'Administrateur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Administration'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #3498db;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background-color: var(--secondary-color);
            color: white;
            transition: all 0.3s;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .sidebar-header {
            padding: 20px;
            background-color: rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .sidebar-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .sidebar-menu {
            padding: 0;
            list-style: none;
            margin-top: 20px;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--primary-color);
        }
        
        .sidebar-menu i {
            margin-right: 15px;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }
        
        .sidebar.collapsed .sidebar-menu span {
            display: none;
        }
        
        .sidebar.collapsed .sidebar-header h3 {
            display: none;
        }
        
        .sidebar.collapsed .sidebar-header .logo-small {
            display: block;
        }
        
        .sidebar .sidebar-header .logo-small {
            display: none;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
            min-height: 100vh;
        }
        
        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }
        
        .top-nav {
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        /* Action Buttons */
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .action-btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .action-btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .action-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        /* Cards */
        .dashboard-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .dashboard-card-icon {
            font-size: 2rem;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        /* Mobile Menu Toggle */
        .menu-toggle {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1100;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            display: none;
            cursor: pointer;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.mobile-visible {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Menu toggle button for mobile -->
    <button class="menu-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>Admin Panel</h3>
            <div class="logo-small">A</div>
        </div>
        
        <?php
        // Déterminer le chemin de base pour les liens
        $base_path = '';
        $current_dir = dirname($_SERVER['PHP_SELF']);
        
        // Si nous sommes dans un sous-dossier du module admin, ajuster le chemin de base
        if (basename($current_dir) !== 'admin') {
            $base_path = '../';
        }
        ?>
        <ul class="sidebar-menu">
            <li>
                <a href="<?php echo $base_path; ?>index.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Tableau de bord</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>manageTeacher.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manageTeacher.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Enseignants</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>manageStudent.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manageStudent.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span>Élèves</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>manageParent.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manageParent.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-friends"></i>
                    <span>Parents</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>manageClass.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manageClass.php' ? 'active' : ''; ?>">
                    <i class="fas fa-school"></i>
                    <span>Classes</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>course.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'course.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Cours</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>examSchedule.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'examSchedule.php' || basename($_SERVER['PHP_SELF']) === 'createExamSchedule.php' || basename($_SERVER['PHP_SELF']) === 'updateExamSchedule.php' || basename($_SERVER['PHP_SELF']) === 'viewExamSchedule.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Examens</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>timeTable.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'timeTable.php' || basename($_SERVER['PHP_SELF']) === 'createTimeTable.php' || basename($_SERVER['PHP_SELF']) === 'updateTimeTable.php' || basename($_SERVER['PHP_SELF']) === 'viewTeacherSchedules.php' || basename($_SERVER['PHP_SELF']) === 'viewClassSchedules.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Emploi du temps</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>manageGrades.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manageGrades.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Notes</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>report.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'report.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Rapports</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>manage_notifications.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_notifications.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>manageStaff.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manageStaff.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-tie"></i>
                    <span>Personnel</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>manageBulletins.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manageBulletins.php' || basename($_SERVER['PHP_SELF']) === 'downloadBulletins.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-pdf"></i>
                    <span>Bulletins</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>payment.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'payment.php' ? 'active' : ''; ?>">
                    <i class="fas fa-money-bill"></i>
                    <span>Paiements</span>
                </a>
            </li>
            <li>
                <a href="<?php echo $base_path; ?>salary.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'salary.php' ? 'active' : ''; ?>">
                    <i class="fas fa-wallet"></i>
                    <span>Salaires</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navigation -->
        <div class="d-flex justify-content-between align-items-center top-nav">
            <div>
                <h1 class="fs-3 fw-semibold">Bienvenue, <?php echo htmlspecialchars($loged_user_name); ?></h1>
            </div>
            <div class="d-flex">
                <a href="<?php echo $base_path; ?>manage_notifications.php" class="action-btn action-btn-primary me-2 text-decoration-none">
                    <i class="fas fa-bell me-1"></i>
                    Notifications
                </a>
                <a href="<?php echo $base_path; ?>logout.php" class="action-btn action-btn-danger text-decoration-none">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    Déconnexion
                </a>
            </div>
        </div>
        
        <!-- Page Content -->
        <div class="content">
            <?php if (isset($content)): ?>
                <?php echo $content; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const sidebarToggle = document.getElementById('sidebarToggle');
            
            // Toggle sidebar on button click (mobile)
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('mobile-visible');
            });
            
            // Double-click on sidebar to collapse/expand
            sidebar.addEventListener('dblclick', function(e) {
                // Only if we're clicking on the sidebar itself or its header, not on links
                if (e.target.classList.contains('sidebar') || e.target.classList.contains('sidebar-header')) {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
                }
            });
        });
    </script>
</body>
</html>
