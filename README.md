# QR Pelplin 1.0.0

Samodzielna wtyczka WordPress: ciemny portal miejski, CMS landing page, treści QR, mapa miejsc, generator SVG/PNG i statystyki. Vue 3 w panelu administracyjnym, WordPress REST API i lekki JavaScript na froncie. Nie wymaga serwera Node.js na hostingu.

## Instalacja

1. Pobierz **qr-pelplin.zip** z [najnowszego wydania](https://github.com/kaulpl/qr-pelplin/releases/latest).
2. WordPress → Wtyczki → Dodaj wtyczkę → Wyślij wtyczkę na serwer → wybierz ZIP → Aktywuj.
3. QR Pelplin → Wygląd i CMS: wybierz logo, zdjęcia, teksty, menu, kolory, sekcje i ich kolejność.
4. Aktywacja utworzy stronę „Odkrywaj Pelplin”. Ustaw ją jako stronę główną w Ustawienia → Czytanie, jeśli chcesz.
5. QR Pelplin → Treści QR: dodaj historię, kategorię, zdjęcie wyróżniające, opcjonalnie współrzędne i multimedia. **Opublikuj wpis**.
6. W panelu wpisu kliknij **Wygeneruj i dołącz QR**. Pobierz SVG do druku lub PNG do publikacji. Oba pliki zostaną dołączone do wpisu w bibliotece mediów. Opcjonalnie wstaw obraz QR do treści i zapisz wpis.
7. Przed drukiem zeskanuj kod na telefonie i sprawdź docelową treść.

Wymagania: WordPress 6.6+, PHP 8.0+, HTTPS zalecane, zapisywalny katalog uploads. SVG jest tworzony wyłącznie przez generator; wtyczka nie odblokowuje dowolnych uploadów SVG. Treści są publiczne — QR jest sposobem na wejście, a nie zabezpieczeniem dostępu.

## CMS

* Nagłówek: nazwa, logo, tło, nadtytuł, nagłówek, opis, przycisk i link.
* Moduły: skróty tematyczne, kategorie, treści, mapa, projekt; włączanie i kolejność.
* Kategorie: osobne zdjęcia, liczba kart i nagłówek.
* Treści: liczba na stronie, paginacja, sortowanie, wyszukiwarka i filtr kategorii przez AJAX.
* Nawigacja: edytowalne linki menu, stopki i mediów społecznościowych.
* Mapa: środek, przybliżenie, grafika i miejsca z wpisów; OpenStreetMap ładuje się dopiero po otwarciu mapy.
* Stopka i projekt: logotyp, teksty, zdjęcie, copyright.
* Wygląd: kolory tła, tekstu i akcentu, dwie typografie.
* System: strona landing page, rozdzielczość PNG, włączanie pomiaru i retencja.

Wybrana strona landing page i treści QR mają niezależny szablon, bez nagłówka aktywnego motywu. Shortcode `[qr_pelplin_landing]` osadzi same sekcje wewnątrz innej strony.

## Stałe kody i statystyki

QR koduje `https://twoja-domena/?qrp_code=stały-token`, a nie zmienny slug wpisu. Przekierowanie 302 prowadzi zawsze do aktualnego permalinku. Zmiana tytułu, slugu lub treści nie wymaga ponownego wydruku. Domena i token muszą pozostać niezmienione; po migracji domeny utrzymuj przekierowanie starej domeny. Nie usuwaj wpisów, których kody są wydrukowane. Niedostępna lub usunięta treść zwraca komunikat i HTTP 410.

**Wyklucz adresy z parametrem `qrp_code` z cache/CDN**. Warstwa cache działająca przed PHP może ominąć naliczanie i przekierowanie. Odsłony treści są liczone przez AJAX, również gdy HTML strony pochodzi z cache.

Panel pokazuje wejścia przez adres QR, odsłony treści, wykres, ranking, zakres 7/30/90/365 dni i eksport dziennych danych CSV. To pomiar otwarcia linku; samo rozpoznanie kodu przez aparat bez otwarcia strony jest niewidoczne. Otwarcie ręcznie skopiowanego linku QR też jest liczone jako wejście QR. Odsłony zawierają również wizyty po QR.

Nie zapisujemy surowego IP ani pełnego User-Agent w bazie. Krótkotrwały HMAC z dzienną składową służy do deduplikacji i pomijania ponownych żądań w ciągu 5 sekund. Jest usuwany po dwóch dniach przez WP-Cron. Dane zagregowane są usuwane według retencji (domyślnie 365 dni). Wartość „odwiedzający” jest przybliżona i liczona na dzień/treść/typ, nie oznacza unikalnych osób w całym okresie. Pomijamy zalogowanych użytkowników mogących edytować daną treść oraz rozpoznane boty. Zmiany ustawień pomiaru uwzględnij w polityce prywatności swojej witryny; sprawdź działanie WP-Cron.

## Rozwój i wydania

Node.js 24 + pnpm 11:

```sh
pnpm install --frozen-lockfile
pnpm build
pnpm check
pnpm test
pnpm package
```

ZIP zawiera wyłącznie katalog `qr-pelplin/` z gotowymi assetami. Zależności npm i źródła Vue nie są wymagane na produkcji. CI sprawdza PHP 8.0/8.3, kompiluje assety, wykonuje testy i publikuje release z ZIP-em po zmianie numeru wersji na main. Aktualizator wtyczki korzysta z oficjalnego assetu ZIP z GitHub Releases.

Historia poprzedniej implementacji jest zachowana w Git i na gałęzi archiwalnej; bieżące drzewo main zostało zastąpione nową wtyczką. Istniejące wpisy `qrp_item`, kategorie `qrp_category` i konfiguracja strony z wcześniejszej wersji są zachowywane. Dezaktywacja i usunięcie wtyczki nie kasują treści ani statystyk.

## Licencje i materiały

Kod: GPL-2.0-or-later. Zdjęcie domyślne: Pliszka-GP, [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Pelplin_-_Katedra_wn%C4%99trze_008GP.jpg), CC BY-SA 4.0; na stronie nakładany jest gradient i kadrowanie CSS. Szczegóły w `qr-pelplin/assets/LICENSES.md`. Domyślny symbol katedry jest autorskim znakiem projektu, nie oficjalnym herbem miasta. Własne logo i fotografie wybierz w CMS.
