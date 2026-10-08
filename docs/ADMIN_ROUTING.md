# QR Pelplin — zatwierdzona architektura

- `https://qr.pelplin.pl` — publiczny frontend Next.js (docelowo Vercel).
- `https://cms.qr.pelplin.pl/wp-admin` — panel administracyjny WordPress na home.pl.
- `https://cms.qr.pelplin.pl/wp-json/wp/v2/qr_entry` — publiczne API treści.
- GitHub `kaulpl/qr-pelplin` — wspólne repozytorium kodu.

Nie używamy proxy /admin. WordPress działa na osobnej subdomenie.

## Kolejność przełączenia

1. Wykonaj pełną kopię plików i bazy danych istniejącego WordPressa.
2. W panelu home.pl dodaj subdomenę cms.qr.pelplin.pl, kierując ją do katalogu WordPressa; zapewnij certyfikat TLS.
3. Przed zmianą adresów zweryfikuj sposób konfiguracji domen w home.pl. Zmień WordPress Address i Site Address na https://cms.qr.pelplin.pl (w razie potrzeby przez WP-CLI lub bazę po kopii zapasowej).
4. Sprawdź logowanie, media, REST API, linki i certyfikat HTTPS. Dla istniejących danych migrację URL wykonuj narzędziem obsługującym serializowane dane, a nie zwykłym SQL REPLACE.
5. Wgraj i aktywuj wtyczkę QR Pelplin; utwórz treść testową.
6. Uruchom frontend Next.js na adresie testowym Vercel z WORDPRESS_API_URL=https://cms.qr.pelplin.pl.
7. Po testach zmień DNS qr.pelplin.pl według instrukcji Vercel. Zachowaj subdomenę CMS skierowaną na home.pl.

Automatyczne wdrażanie wtyczki na home.pl wymaga odrębnej konfiguracji bezpiecznego dostępu SFTP/SSH; nie jest jeszcze aktywne.
