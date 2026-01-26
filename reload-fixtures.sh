#!/bin/bash
# reload-fixtures.sh

DB_NAME="cfa_db"
DB_USER="root"
DB_PASS=",/a%Ew=Rssh5"

echo "Désactivation des contraintes FK..."
mysql -u $DB_USER -p$DB_PASS $DB_NAME -e "SET FOREIGN_KEY_CHECKS=0;"

echo "Rechargement des fixtures..."
php bin/console doctrine:fixtures:load --purge-with-truncate --no-interaction

echo "Réactivation des contraintes FK..."
mysql -u $DB_USER -p$DB_PASS $DB_NAME -e "SET FOREIGN_KEY_CHECKS=1;"

echo "Terminé !"
