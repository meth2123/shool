# Guide de déploiement sur Render.com

Ce guide vous aidera à configurer correctement votre application School Manager sur Render.com.

## 1. Configuration de la base de données externe

Render.com ne fournit pas de service MySQL intégré. Vous devez utiliser un service MySQL externe comme :
- [PlanetScale](https://planetscale.com/) (offre un plan gratuit)
- [Railway](https://railway.app/) (offre un plan gratuit)
- [AWS RDS](https://aws.amazon.com/rds/mysql/)
- [DigitalOcean](https://www.digitalocean.com/products/managed-databases-mysql)

### Étapes pour configurer une base de données MySQL externe :

1. Créez un compte sur l'un des services ci-dessus
2. Créez une nouvelle base de données MySQL
3. Notez les informations de connexion :
   - Hôte (hostname)
   - Port (généralement 3306)
   - Nom d'utilisateur
   - Mot de passe
   - Nom de la base de données

## 2. Importation de votre base de données

Vous devez exporter votre base de données locale et l'importer dans votre base de données externe :

1. Exportez votre base de données locale depuis phpMyAdmin ou avec la commande :
   ```
   mysqldump -u root -p gestion > gestion_backup.sql
   ```

2. Importez le fichier SQL dans votre base de données externe (la méthode dépend du service choisi)

## 3. Configuration de Render.com

### Créer un nouveau service Web

1. Connectez-vous à [Render.com](https://render.com/)
2. Cliquez sur "New" puis "Web Service"
3. Connectez votre dépôt GitHub ou utilisez l'option "Upload"
4. Configurez le service :
   - **Name**: School Manager
   - **Runtime**: PHP
   - **Build Command**: `composer install`
   - **Start Command**: `php -S 0.0.0.0:$PORT -t .`

### Configurer les variables d'environnement

Dans votre service Render.com, allez dans "Environment" et ajoutez les variables suivantes :

```
RENDER=true
IS_RENDER=true
EXTERNAL_DATABASE_HOST=votre-hote-mysql
EXTERNAL_DATABASE_PORT=3306
EXTERNAL_DATABASE_USER=votre-utilisateur
EXTERNAL_DATABASE_PASSWORD=votre-mot-de-passe
EXTERNAL_DATABASE_NAME=votre-base-de-donnees
```

Remplacez les valeurs par vos informations de connexion à la base de données externe.

## 4. Vérification et dépannage

Après le déploiement, vérifiez les logs dans Render.com pour vous assurer que la connexion à la base de données fonctionne correctement.

Si vous rencontrez des erreurs :

1. Vérifiez que les variables d'environnement sont correctement définies
2. Assurez-vous que votre base de données externe accepte les connexions depuis Render.com (vérifiez les règles de pare-feu)
3. Vérifiez que les tables et les données ont été correctement importées

## 5. Optimisations supplémentaires

Pour améliorer les performances de votre application sur Render.com :

1. Activez le cache d'opcode PHP en ajoutant un fichier `php.ini` à la racine de votre projet :
   ```ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.interned_strings_buffer=8
   opcache.max_accelerated_files=4000
   opcache.revalidate_freq=60
   opcache.fast_shutdown=1
   ```

2. Configurez un CDN pour les fichiers statiques
3. Optimisez les images et les ressources JavaScript/CSS

## Support

Si vous rencontrez des problèmes avec le déploiement, consultez la [documentation officielle de Render.com](https://render.com/docs) ou contactez leur support.
