<?php
require_once '../service/db_utils.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guide Parent - SchoolManager</title>
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
        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Guide Parent SchoolManager</h1>
        
        <!-- Table des matières -->
        <div class="feature-section">
            <h2>Table des matières</h2>
            <ul class="list-group">
                <li class="list-group-item"><a href="#connexion">1. Première connexion</a></li>
                <li class="list-group-item"><a href="#profil">2. Gestion du profil</a></li>
                <li class="list-group-item"><a href="#enfants">3. Gestion des enfants</a></li>
                <li class="list-group-item"><a href="#suivi">4. Suivi scolaire</a></li>
                <li class="list-group-item"><a href="#paiements">5. Paiements et factures</a></li>
                <li class="list-group-item"><a href="#communication">6. Communication</a></li>
                <li class="list-group-item"><a href="#absences">7. Gestion des absences</a></li>
                <li class="list-group-item"><a href="#documents">8. Documents et formulaires</a></li>
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
                        <li>Utilisez les identifiants fournis par l'école :
                            <ul>
                                <li>Identifiant : votre email</li>
                                <li>Mot de passe temporaire : envoyé par email</li>
                            </ul>
                        </li>
                        <li>Changez votre mot de passe lors de la première connexion</li>
                    </ol>
                    <div class="tip-box">
                        <strong>Conseil :</strong> Activez l'authentification à deux facteurs pour plus de sécurité.
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
                                <li>Coordonnées principales</li>
                                <li>Coordonnées secondaires</li>
                                <li>Informations de contact d'urgence</li>
                            </ul>
                        </li>
                        <li>Préférences
                            <ul>
                                <li>Langue d'interface</li>
                                <li>Notifications</li>
                                <li>Méthodes de contact préférées</li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Enfants -->
        <div id="enfants" class="feature-section">
            <h2>3. Gestion des enfants</h2>
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Suivre vos enfants</h5>
                    <ul class="step-list">
                        <li>Profil de l'enfant
                            <ul>
                                <li>Informations personnelles</li>
                                <li>Classe et niveau</li>
                                <li>Enseignants</li>
                            </ul>
                        </li>
                        <li>Autorisations
                            <ul>
                                <li>Sorties scolaires</li>
                                <li>Utilisation des photos</li>
                                <li>Accès aux ressources</li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Suivi scolaire -->
        <div id="suivi" class="feature-section">
            <h2>4. Suivi scolaire</h2>
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Suivre la scolarité</h5>
                    <ul class="step-list">
                        <li>Notes et résultats
                            <ul>
                                <li>Bulletins</li>
                                <li>Notes par matière</li>
                                <li>Appréciations</li>
                            </ul>
                        </li>
                        <li>Emploi du temps
                            <ul>
                                <li>Horaires des cours</li>
                                <li>Activités extra-scolaires</li>
                                <li>Changements d'horaires</li>
                            </ul>
                        </li>
                        <li>Devoirs et travaux
                            <ul>
                                <li>Devoirs à faire</li>
                                <li>Dates de rendu</li>
                                <li>Ressources nécessaires</li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Paiements -->
        <div id="paiements" class="feature-section">
            <h2>5. Paiements et factures</h2>
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Gérer les paiements</h5>
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
                                <li>Paiements récurrents</li>
                                <li>Suivi des transactions</li>
                            </ul>
                        </li>
                    </ul>
                    <div class="warning-box">
                        <strong>Important :</strong> Conservez une copie de tous vos reçus de paiement.
                    </div>
                </div>
            </div>
        </div>

        <!-- Communication -->
        <div id="communication" class="feature-section">
            <h2>6. Communication</h2>
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Communiquer avec l'école</h5>
                    <ul class="step-list">
                        <li>Messagerie
                            <ul>
                                <li>Messages aux enseignants</li>
                                <li>Messages à l'administration</li>
                                <li>Annonces de l'école</li>
                            </ul>
                        </li>
                        <li>Rendez-vous
                            <ul>
                                <li>Demander un rendez-vous</li>
                                <li>Consulter les disponibilités</li>
                                <li>Confirmer les rendez-vous</li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Absences -->
        <div id="absences" class="feature-section">
            <h2>7. Gestion des absences</h2>
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Gérer les absences</h5>
                    <ul class="step-list">
                        <li>Signaler une absence
                            <ul>
                                <li>Absence prévue</li>
                                <li>Absence imprévue</li>
                                <li>Justification</li>
                            </ul>
                        </li>
                        <li>Suivi des absences
                            <ul>
                                <li>Historique</li>
                                <li>Justificatifs</li>
                                <li>Notifications</li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Documents -->
        <div id="documents" class="feature-section">
            <h2>8. Documents et formulaires</h2>
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Accéder aux documents</h5>
                    <ul class="step-list">
                        <li>Documents administratifs
                            <ul>
                                <li>Inscriptions</li>
                                <li>Autorisations</li>
                                <li>Certificats</li>
                            </ul>
                        </li>
                        <li>Formulaires
                            <ul>
                                <li>Demandes diverses</li>
                                <li>Inscriptions aux activités</li>
                                <li>Changements d'information</li>
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
                    <li>Répondez rapidement aux messages importants</li>
                    <li>Signalez les absences dès que possible</li>
                    <li>Effectuez les paiements dans les délais</li>
                    <li>Gardez vos informations de contact à jour</li>
                    <li>Sauvegardez les documents importants</li>
                    <li>Participez aux réunions parents-enseignants</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>