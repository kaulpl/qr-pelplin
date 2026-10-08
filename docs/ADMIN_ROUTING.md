# Docelowe adresy QR Pelplin

- `https://qr.pelplin.pl/` — Next.js.
- `https://qr.pelplin.pl/admin` — wejście do panelu WordPress (wymaga reverse proxy i sprawdzenia działania cookies, przekierowań, REST API, uploadów, wp-login.php, wp-admin, wp-content).

## Ważne ograniczenie

Obecny WordPress działa pod qr.pelplin.pl na hostingu współdzielonym home.pl. Nie można skierować jednego hosta DNS jednocześnie na home.pl i Vercel według ścieżki URL. Konieczny jest reverse proxy/edge router albo osobna domena origin WordPressa z kontrolowanym proxy dla /admin. Sam redirect /admin do /wp-admin nie zachowa publicznego adresu /admin.

## Zalecana ścieżka

1. Wykonać backup bazy i plików WordPressa.
2. Potwierdzić możliwości hostingu home.pl i wybrać origin backendu (np. cms.qr.pelplin.pl).
3. Uruchomić Next.js na adresie testowym, bez zmiany DNS produkcyjnego.
4. Zdecydować o warstwie reverse proxy dla /admin i tras systemowych WordPressa. Zabezpieczyć endpointy i cookies, zweryfikować media, logowanie, formularze i wylogowanie.
5. Dopiero po testach przełączyć qr.pelplin.pl.

Nie traktować `/admin` jako gotowego mechanizmu bezpieczeństwa; konieczne są aktualizacje, uprawnienia i MFA. Jeśli routing pod jednym hostem będzie zbyt skomplikowany, alternatywą jest osobny origin `cms.qr.pelplin.pl` oraz przekierowanie z `/admin`, które zmieni adres w przeglądarce.
