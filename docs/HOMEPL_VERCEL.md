# Wdrożenie QR Pelplin — home.pl + Vercel

## Docelowy układ
- home.pl: WordPress i baza danych na `cms.qr.pelplin.pl`
- Vercel: Next.js na `qr.pelplin.pl`
- GitHub: monorepo `kaulpl/qr-pelplin`

## Etap 1 — WordPress
1. W panelu home.pl skonfiguruj subdomenę `cms.qr.pelplin.pl` wskazującą na istniejącą instalację WordPressa. Jeśli WordPress działa już na `qr.pelplin.pl`, zaplanuj migrację adresu WordPressa przed zmianą DNS; najpierw wykonaj kopię plików i bazy.
2. Włącz HTTPS, ustaw adresy WordPressa w Ustawienia → Ogólne na adres CMS.
3. Wgraj folder `wordpress/plugins/qr-pelplin` do `wp-content/plugins/` i aktywuj.
4. Ustaw bezpośrednie odnośniki na „Nazwa wpisu”. Zweryfikuj publiczny endpoint `https://cms.qr.pelplin.pl/wp-json/wp/v2/qr_entry`.
5. Dodaj pierwszą treść QR i opublikuj ją. Endpoint powinien zwrócić JSON.

## Etap 2 — Vercel
1. Importuj repozytorium `kaulpl/qr-pelplin` jako projekt Vercel.
2. Ustaw Root Directory na `apps/web`; Framework Preset: Next.js.
3. Dodaj zmienną środowiskową `WORDPRESS_API_URL=https://cms.qr.pelplin.pl` (bez końcowego ukośnika).
4. Wdróż gałąź testową; sprawdź pobieranie wpisów i ścieżki `/q/<slug>`.
5. Dopiero po testach przypnij `qr.pelplin.pl` i ustaw rekordy DNS według wartości podanych przez Vercel (nie wpisuj rekordów na podstawie przykładu).

## Etap 3 — aktualizacje
- Vercel automatycznie wdraża zmiany frontendu z podłączonego repozytorium.
- Wtyczka WordPress na razie wymaga ręcznego wdrożenia ZIP/SFTP. Automatyzację aktualizacji przygotujemy po potwierdzeniu dostępu SFTP/SSH na home.pl.
- Nie przechowuj haseł, tokenów, danych osobowych ani plików .env w publicznym repozytorium.
- Ustaw zabezpieczenia wp-admin, kopie zapasowe i osobne konta redaktorów.

## Ważne
Nie zmieniaj DNS publicznej strony przed uruchomieniem i przetestowaniem CMS i frontendu.