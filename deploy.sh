#!/bin/bash
# DEPLOYMENT SCRIPT für Monatsrechnung-Feature
# Auf dem Plesk Server ausführen

set -e

echo "==================================================="
echo "Monatsrechnung-Feature Deployment"
echo "==================================================="

cd /var/www/vhosts/thss.online/service-tickets.thss.online

echo ""
echo "1. Running Database Migrations..."
php artisan migrate

echo ""
echo "2. Clearing Cache..."
php artisan config:clear
php artisan route:clear
php artisan cache:clear

echo ""
echo "3. Setting Permissions..."
chown -R tobiashuber:psacln storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
find storage -type f -exec chmod 664 {} \;
find bootstrap/cache -type f -exec chmod 664 {} \;

echo ""
echo "==================================================="
echo "✅ Deployment abgeschlossen!"
echo "==================================================="
echo ""
echo "Nächste Schritte:"
echo "1. Öffne https://service-tickets.thss.online/admin/monatsrechnungen"
echo "2. Teste Monatsrechnung-Generierung im Kundenportal"
echo "3. Prüfe ob die Badges angezeigt werden"
echo ""
