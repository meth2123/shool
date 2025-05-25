<?php
require_once '../service/db_utils.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guide Élève - SchoolManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .feature-section {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .step-list {
            list-style-type: none;
            padding-left: 0;
        }
        .step-list li {
            margin-bottom: 15px;
            padding-left: 25px;
            position: relative;
        }
        .step-list li:before {
            content: "→";
            position: absolute;
            left: 0;
            color: #0d6efd;
        }
        .tip-box {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Guide Élève SchoolManager</h1>
        
        <!-- Table des matières -->
        <div class="feature-section">
            <h2>Table des matières</h2>
            <ul class="list-group">
                <li class="list-group-item"><a href="#connexion">1. Première connexion</a></li>
                <li class="list-group-item"><a href="#profil">2. Gestion du profil</a></li>
                <li class="list-group-item"><a href="#emploi-temps">3. Emploi du temps</a></li>
                <li class="list-group-item"><a href="#notes">4. Consulter les notes</a></li>
                <li class="list-group-item"><a href="#cours">5. Accès aux cours</a></li>
                <li class="list-group-item"><a href="#devoirs">6. Gestion des devoirs</a></li>
                <li class="list-group-item"><a href="#paiements">7. Paiements et factures</a></li>
                <li class="list-group-item"><a href="#communication">8. Communication</a></li>
            </ul>
        </div>

        <!-- Connexion -->
        <div id="connexion" class="feature-section">
            <h2>1. Première connexion</h2>
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Comment se connecter</h5>
                    <ol class="step-list">
                        <li>Accédez à la page de connexion</li>
                        <li>Utilisez vos identifiants fournis par l'école :
                            <ul>
                                <li>Identifiant : votre numéro d'étudiant</li>
                                <li>Mot de passe temporaire : date de naissance (JJMMAAAA)</li>
                            </ul>
                        </li>
                        <li>Changez votre mot de passe lors de la première connexion</li>
                    </ol>
                    <div class="tip-box">
                        <strong>Conseil :</strong> Choisissez un mot de passe fort et ne le partagez avec personne.
                    </div>
                </div>
            </div>
        </div>

        <!-- Profil -->
        <div id="profil" class="feature-section">
            <h2>2. Gestion du profil</h2>
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Personnaliser votre profil</h5>
                    <ul class="step-list">
                        <li>Informations personnelles
                            <ul>
                                <li>Photo de profil</li>
                                <li>Coordonnées</li>
                                <li>Informations de contact d'urgence</li>
                            </ul>
                        </li>
                        <li>Préférences
                            <ul>
                                <li>Langue d'interface</li>
                                <li>Notifications</li>
                                <li>Thème d'affichage</li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Emploi du temps -->
        <div id="emploi-temps" class="feature-section">
            <h2>3. Emploi du temps</h2>
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Consulter votre emploi du temps</h5>
                    <ul class="step-list">
                        <li>Vue hebdomadaire
                            <ul>
                                <li>Horaires des cours</li>
                                <li>Salles de classe</li>
                                <li>Enseignants</li>
                            </ul>
                        </li>
                        <li>Vue mensuelle</li>
                        <li>Notifications de changements</li>
                    </ul>
                    <div class="tip-box">
                        <strong>Astuce :</strong> Activez les notifications pour être alerté des changements d'emploi du temps.
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div id="notes" class="feature-section">
            <h2>4. Consulter les notes</h2>
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Accéder à vos résultats</h5>
                    <ul class="step-list">
                        <li>Notes par matière
                            <ul>
                                <li>Devoirs</li>
                                <li>Compositions</li>
                                <li>Moyennes</li>
                            </ul>
                        </li>
                        <li>Bulletins
                            <ul>
                                <li>Périodiques</li>
                                <li>Annuels</li>
                                <li>Appréciations</li>
                            </ul>
                        </li>
                        <li>Statistiques de progression</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Cours -->
        <div id="cours" class="feature-section">
            <h2>5. Accès aux cours</h2>
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Ressources pédagogiques</h5>
                    <ul class="step-list">
                        <li>Documents de cours
                            <ul>
                                <li>Cours en ligne</li>
                                <li>Supports de cours</li>
                                <li>Exercices</li>
                            </ul>
                        </li>
                        <li>Ressources complémentaires</li>
                        <li>Bibliothèque numérique</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Devoirs -->
        <div id="devoirs" class="feature-section">
            <h2>6. Gestion des devoirs</h2>
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Suivre vos devoirs</h5>
                    <ul class="step-list">
                        <li>Devoirs à rendre
                            <ul>
                                <li>Dates limites</li>
                                <li>Instructions</li>
                                <li>Documents à joindre</li>
                            </ul>
                        </li>
                        <li>Rendu des devoirs
                            <ul>
                                <li>Upload de fichiers</li>
                                <li>Confirmation de soumission</li>
                                <li>Suivi des corrections</li>
                            </ul>
                        </li>
                    </ul>
                    <div class="tip-box">
                        <strong>Important :</strong> Respectez les dates limites de rendu des devoirs.
                    </div>
                </div>
            </div>
        </div>

        <!-- Paiements -->
        <div id="paiements" class="feature-section">
            <h2>7. Paiements et factures</h2>
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Gérer vos paiements</h5>
                    <ul class="step-list">
                        <li>Factures
                            <ul>
                                <li>Consulter les factures</li>
                                <li>Télécharger les reçus</li>
                                <li>Historique des paiements</li>
                            </ul>
                        </li>
                        <li>Paiements en ligne
                            <ul>
                                <li>Méthodes de paiement</li>
                                <li>Confirmation de paiement</li>
                                <li>Suivi des transactions</li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Communication -->
        <div id="communication" class="feature-section">
            <h2>8. Communication</h2>
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Communiquer avec l'école</h5>
                    <ul class="step-list">
                        <li>Messagerie
                            <ul>
                                <li>Messages aux enseignants</li>
                                <li>Annonces de l'école</li>
                                <li>Notifications</li>
                            </ul>
                        </li>
                        <li>Absences
                            <ul>
                                <li>Signaler une absence</li>
                                <li>Justifier une absence</li>
                                <li>Consulter l'historique</li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Conseils -->
        <div class="feature-section bg-light">
            <h2>Conseils pratiques</h2>
            <div class="alert alert-info">
                <h5>Points importants à retenir :</h5>
                <ul>
                    <li>Connectez-vous régulièrement pour vérifier les nouvelles informations</li>
                    <li>Sauvegardez vos documents importants</li>
                    <li>Respectez les délais de rendu des devoirs</li>
                    <li>Vérifiez régulièrement votre messagerie</li>
                    <li>Signalez rapidement toute absence</li>
                    <li>Gardez vos informations de contact à jour</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>