# QR Pelplin 1.0.0

Nowa, samodzielna implementacja wtyczki WordPress.

* Ciemny landing page inspirowany trzecim wariantem wizualizacji: złote akcenty, nagłówki szeryfowe, zdjęcia, kategorie i mapa.
* Panel CMS Vue 3: logo, grafiki, nagłówki, menu, stopka, projekt, kolory, kolejność i widoczność modułów, limity wyświetlania.
* Edytor blokowy dla Treści QR: kategorie, zdjęcia wyróżniające, lokalizacje, galerie, audio i wideo.
* Generator QR przy wpisie: stały token, zapisywane załączniki SVG i PNG oraz opcjonalne wstawienie obrazu do edytora.
* Wyszukiwanie, filtrowanie i paginacja AJAX. Mapa OpenStreetMap z wyborem miejsca.
* Statystyki wejść przez QR i odsłon, wykres, ranking, okresy i eksport CSV. Pomiar przybliżony, bez zapisu surowych IP.
* Aktualizacje przez GitHub Releases. Paczka ZIP gotowa do instalacji w WordPressie, bez Node.js na serwerze.

Instalacja: Wtyczki → Dodaj → Wyślij ZIP → Aktywuj. Konfiguracja: QR Pelplin → Wygląd i CMS.

Przed drukiem opublikuj treść i sprawdź kod telefonem. Wyklucz `?qrp_code=` z cache/CDN. Wydrukowane kody wymagają zachowania domeny i wpisu.

Wymagania: WordPress 6.6+, PHP 8.0+. Poprzednia historia Git i istniejące treści WordPress są zachowane.
