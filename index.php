<?php
$login_code = isset($_REQUEST['login']) ? $_REQUEST['login'] : '1';
$reset_success = isset($_REQUEST['reset']) ? $_REQUEST['reset'] : '';
$reset_error = isset($_REQUEST['error']) ? $_REQUEST['error'] : '';

if($login_code=="false"){
    $login_message = "Identifiants incorrects !";
    $login_type = "error";
} else {
    $login_message = "Veuillez vous connecter";
    $login_type = "info";
}

if(isset($_GET['error'])) {
    $error = $_GET['error'];
    $error_message = '';
    $student_name = isset($_GET['student_name']) ? htmlspecialchars($_GET['student_name']) : '';
    
    switch($error) {
        case 'student_not_found':
            $error_message = "L'étudiant n'a pas été trouvé dans la base de données.";
            break;
        case 'student_no_class':
            $error_message = "L'étudiant " . $student_name . " n'a pas de classe assignée. Veuillez contacter l'administrateur pour assigner une classe.";
            break;
        case 'student_class_not_found':
            $error_message = "La classe de l'étudiant n'a pas été trouvée. Veuillez contacter l'administrateur.";
            break;
        case 'login':
            $error_message = "Identifiant ou mot de passe incorrect.";
            break;
        default:
            $error_message = "Une erreur est survenue. Veuillez réessayer.";
    }
    
    if($error_message) {
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Erreur!</strong>
                <span class="block sm:inline">' . $error_message . '</span>
              </div>';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SchoolManager - Système de Gestion Scolaire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hero-section {
            padding: 4rem 0;
            background-color: #f8f9fa;
        }
        .feature-card {
            height: 100%;
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .pricing-card {
            height: 100%;
            transition: transform 0.3s;
        }
        .pricing-card:hover {
            transform: translateY(-5px);
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .btn-success {
            background-color: #198754;
            border-color: #198754;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="source/logo.jpg" class="me-2" width="40" height="40" alt="Logo"/>
                <span class="fw-bold">SchoolManager</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Fonctionnalités</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pricing">Tarifs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="documentation/index.php">Documentation</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-primary" href="login.php">Se connecter</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-success" href="module/subscription/register.php">S'abonner</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section py-5">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-10">
                    <h1 class="display-4 fw-bold mb-4">Gérez votre établissement scolaire en toute simplicité</h1>
                    <p class="lead mb-5">SchoolManager est une solution complète pour la gestion administrative et pédagogique de votre établissement scolaire.</p>
                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                        <a href="login.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                        </a>
                        <a href="module/subscription/register.php" class="btn btn-success btn-lg">
                            <i class="fas fa-crown me-2"></i>S'abonner maintenant
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-white">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="fw-bold mb-3">Fonctionnalités principales</h2>
                    <p class="lead text-muted">Tout ce dont vous avez besoin pour gérer efficacement votre établissement</p>
                </div>
            </div>
            <div class="row g-4">
                <!-- Gestion des étudiants -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 feature-card shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary p-3 rounded-3 me-3 text-white">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <h5 class="card-title mb-0">Gestion des étudiants</h5>
                            </div>
                            <p class="card-text text-muted">Inscription, suivi des notes, gestion des absences et bien plus encore.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Gestion des enseignants -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 feature-card shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-success p-3 rounded-3 me-3 text-white">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <h5 class="card-title mb-0">Gestion des enseignants</h5>
                            </div>
                            <p class="card-text text-muted">Planning des cours, gestion des emplois du temps, suivi des performances.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Gestion financière -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 feature-card shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-info p-3 rounded-3 me-3 text-white">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <h5 class="card-title mb-0">Gestion financière</h5>
                            </div>
                            <p class="card-text text-muted">Suivi des paiements, gestion des frais de scolarité, rapports financiers.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-5 bg-light">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="fw-bold mb-3">Tarifs simples et transparents</h2>
                    <p class="lead text-muted">Un seul forfait pour tous les établissements</p>
                </div>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card pricing-card shadow">
                        <div class="card-body p-5 text-center">
                            <h3 class="card-title fw-bold mb-3">Forfait Standard</h3>
                            <p class="text-muted mb-4">Accès à toutes les fonctionnalités</p>
                            
                            <div class="mb-4">
                                <span class="display-5 fw-bold">15 000 FCFA</span>
                                <span class="text-muted">/mois</span>
                            </div>
                            
                            <ul class="list-unstyled text-start mb-4">
                                <li class="mb-3 d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <span>Gestion complète des étudiants et enseignants</span>
                                </li>
                                <li class="mb-3 d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <span>Suivi des notes et des absences</span>
                                </li>
                                <li class="mb-3 d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <span>Gestion financière et rapports</span>
                                </li>
                                <li class="mb-3 d-flex align-items-center">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <span>Support technique 24/7</span>
                                </li>
                            </ul>
                            
                            <a href="module/subscription/register.php" class="btn btn-success btn-lg w-100">
                                S'abonner maintenant
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="mb-3">SchoolManager</h5>
                    <p class="small">Solution complète pour la gestion administrative et pédagogique de votre établissement scolaire.</p>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="mb-3">Liens rapides</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="login.php" class="text-white text-decoration-none">Se connecter</a></li>
                        <li class="mb-2"><a href="module/subscription/register.php" class="text-white text-decoration-none">S'abonner</a></li>
                        <li class="mb-2"><a href="documentation/index.php" class="text-white text-decoration-none">Documentation</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3">Contact</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><i class="fas fa-envelope me-2"></i>contact@schoolmanager.com</li>
                        <li class="mb-2"><i class="fas fa-phone me-2"></i>+221 77 807 25 70</li>
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i>Senegal, Dakar</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="small mb-md-0">&copy; <?php echo date('Y'); ?> SchoolManager. Tous droits réservés.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <div class="d-inline-flex">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
