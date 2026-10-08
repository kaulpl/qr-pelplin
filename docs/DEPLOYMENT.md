# Wdrożenie

WordPress działa na hostingu współdzielonym, Next.js wymaga hostingu z Node.js. Zainstaluj wtyczkę z wordpress/plugins/qr-pelplin w wp-content/plugins. Skonfiguruj WORDPRESS_API_URL w hostingu Next.js. Skieruj domenę qr.pelplin.pl na frontend, a cms.qr.pelplin.pl na WordPress. Automatyczne wdrażanie wymaga późniejszego skonfigurowania dostępu SSH/SFTP i sekretów GitHub Actions. Nie zapisuj haseł w repozytorium.
