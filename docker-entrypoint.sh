#!/bin/bash
set -e

# Attendre que MySQL soit prêt
if [ -n "$DB_HOST" ]; then
  echo "Attente de la disponibilité de MySQL..."
  
  ATTEMPTS=0
  MAX_ATTEMPTS=30
  
  until mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -e "SELECT 1" >/dev/null 2>&1 || [ $ATTEMPTS -eq $MAX_ATTEMPTS ]; do
    ATTEMPTS=$((ATTEMPTS+1))
    echo "Attente de MySQL... tentative $ATTEMPTS/$MAX_ATTEMPTS"
    sleep 2
  done
  
  if [ $ATTEMPTS -eq $MAX_ATTEMPTS ]; then
    echo "Impossible de se connecter à MySQL après $MAX_ATTEMPTS tentatives. Abandon."
    exit 1
  fi
  
  echo "MySQL est disponible !"
  
  # Vérifier si la base de données existe
  if ! mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME" >/dev/null 2>&1; then
    echo "La base de données $DB_NAME n'existe pas. Création et initialisation en cours..."
    
    # Créer la base de données
    echo "Création de la base de données $DB_NAME..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    
    # Utiliser le fichier SQL principal pour l'initialisation
    if [ -f "/var/www/html/init-db.sql" ]; then
      echo "Initialisation de la base de données avec init-db.sql..."
      # Supprimer les lignes SOURCE pour éviter les problèmes
      sed '/^SOURCE/d' /var/www/html/init-db.sql > /tmp/init-db-modified.sql
      # Ajouter USE database au début si nécessaire
      echo "USE \`$DB_NAME\`;" > /tmp/init-db-final.sql
      cat /tmp/init-db-modified.sql >> /tmp/init-db-final.sql
      # Exécuter le script modifié
      mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" < /tmp/init-db-final.sql
      echo "Base de données initialisée avec succès."
      
      # Exécuter le script create_database.sql si disponible
      if [ -f "/var/www/html/create_database.sql" ]; then
        echo "Exécution du script create_database.sql..."
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < /var/www/html/create_database.sql
        echo "Script create_database.sql exécuté avec succès."
      fi
      
      # Exécuter le script payment_tables.sql si disponible
      if [ -f "/var/www/html/payment_tables.sql" ]; then
        echo "Exécution du script payment_tables.sql..."
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < /var/www/html/payment_tables.sql
        echo "Script payment_tables.sql exécuté avec succès."
      fi
    else
      echo "Fichier init-db.sql non trouvé. Utilisation des scripts alternatifs..."
      
      # Exécuter le script create_database.sql si disponible
      if [ -f "/var/www/html/create_database.sql" ]; then
        echo "Exécution du script create_database.sql..."
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < /var/www/html/create_database.sql
        echo "Script create_database.sql exécuté avec succès."
      fi
      
      # Exécuter le script payment_tables.sql si disponible
      if [ -f "/var/www/html/payment_tables.sql" ]; then
        echo "Exécution du script payment_tables.sql..."
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < /var/www/html/payment_tables.sql
        echo "Script payment_tables.sql exécuté avec succès."
      fi
    fi
  else
    echo "La base de données $DB_NAME existe déjà."
  fi
fi

# Afficher les informations de connexion à la base de données pour le débogage
echo "Configuration de la base de données :"
echo "Host: $DB_HOST"
echo "User: $DB_USER"
echo "Database: $DB_NAME"

# Définir les permissions correctes
echo "Configuration des permissions..."
chown -R www-data:www-data /var/www/html
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

# Exécuter la commande fournie (généralement apache2-foreground)
exec "$@"
