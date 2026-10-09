# QR Pelplin 1.7.5

Samodzielna wtyczka WordPress: ciemny portal miejski, CMS landing page, treści QR, mapa miejsc, generator SVG/PNG i statystyki. Vue 3 w panelu administracyjnym, WordPress REST API i lekki JavaScript na froncie. Nie wymaga serwera Node.js na hostingu.

## Instalacja

1. Pobierz **qr-pelplin.zip** z [najnowszego wydania](https://github.com/kaulpl/qr-pelplin/releases/latest).
2. WordPress → Wtyczki → Dodaj wtyczkę → Wyślij wtyczkę na serwer → wybierz ZIP → Aktywuj.
3. QR Pelplin → Wygląd i CMS: wybierz logo, zdjęcia, teksty, menu, kolory, sekcje i ich kolejność.
4. Aktywacja utworzy stronę „Odkrywaj Pelplin”. Ustaw ją jako stronę główną w Ustawienia → Czytanie, jeśli chcesz.
5. QR Pelplin → Treści QR: dodaj historię, kategorię, zdjęcie wyróżniające, opcjonalnie współrzędne i multimedia. **Opublikuj wpis**.
6. Po publikacji kod QR powstaje automatycznie, tylko raz. Pobierz SVG lub PNG w panelu wpisu albo zakładce Kody QR. Możesz zmieniać docelową treść, zachowując kod.
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

## Aktualizacje, pliki i widoki (1.1.0)

W **Wygląd i CMS → Aktualizacje** użyj **Sprawdź aktualizacje na GitHubie**. Nowe opublikowane wydanie wyświetli przycisk **Zaktualizuj do…**. Uruchamia on standardowy instalator WordPressa z kontrolą uprawnień i nonce, również na hostingach wymagających danych FTP. Błąd połączenia z GitHubem jest pokazany jako błąd, a nie informacja o aktualności. Instalacja wymaga uprawnienia update_plugins i włączonych modyfikacji plików.

Przy wpisie znajdziesz panel **Prezentacja, pliki i galerie**. Wybierz plik główny, galerię zdjęć i/lub uporządkowane strony PDF/JPG z biblioteki mediów. Dodatkowo możesz używać standardowych bloków Galeria, Obraz, Plik i Audio.

* **Automatycznie**: wpis z treścią, galerią lub stronami dokumentów wyświetla stronę. Sam PDF otwiera podgląd, JPG jest pobierany; sam MP3 otwiera odtwarzacz.
* **Strona z treścią**: prezentuje treść i załączone materiały w layoucie portalu.
* **Pobierz plik główny**: QR i kafelek kierują do pobrania pliku głównego.
* **Podgląd pliku**: PDF lub obraz jest prezentowany jako strona materiału; PDF ma również link do otwarcia w osobnym oknie.
* **Odtwarzacz MP3**: prezentuje odtwarzacz z próbą rozpoczęcia odtwarzania. Przeglądarki mobilne mogą wymagać dotknięcia przycisku Play.

Zmiana materiału lub trybu nie zmienia tokenu QR. PDF/JPG może być dodatkiem do zwykłej treści. Dla plików lokalnych WordPress wysyła nagłówek Content-Disposition: attachment; przy plikach przeniesionych do CDN/storage zachowanie pobierania zależy od nagłówków tego storage. Galeria otwiera pełne zdjęcia w osobnym oknie. Podgląd PDF korzysta z lokalnego PDF.js, z linkiem zapasowym do osobnego okna.

Kategorie mają pełny layout portalu, przełączniki kategorii, kafelki wpisów, wyszukiwanie ograniczone do danej kategorii i przycisk ładowania kolejnych kart. Pojedynczy wpis na desktopie zachowuje layout; na telefonie ma wąski nagłówek, tytuł i samą treść/materiały bez rozbudowanej nawigacji i stopki.

Domyślne logo łączy przekazany znak Pelplina z dekoracyjnym symbolem QR w kolorach portalu. Możesz zastąpić je własną grafiką w CMS. Symbol QR w logo jest elementem identyfikacji wizualnej, a nie kodem do skanowania.

## Stałe kody i statystyki

QR koduje `https://twoja-domena/q/stały-token/`, a nie zmienny slug wpisu. Przekierowanie 302 prowadzi zawsze do aktualnego permalinku. Zmiana tytułu, slugu lub treści nie wymaga ponownego wydruku. Domena i token muszą pozostać niezmienione; po migracji domeny utrzymuj przekierowanie starej domeny. Nie usuwaj wpisów, których kody są wydrukowane. Niedostępna lub usunięta treść zwraca komunikat i HTTP 410.

**Wyklucz adresy /q/* oraz adresy z parametrem `qrp_code` lub `qrp_download` z cache/CDN**. Warstwa cache działająca przed PHP może ominąć naliczanie i przekierowanie. Odsłony treści są liczone przez AJAX, również gdy HTML strony pochodzi z cache.

Panel pokazuje wejścia przez adres QR, odsłony treści, wykres, ranking, zakres 7/30/90/365 dni i eksport dziennych danych CSV. To pomiar otwarcia linku; samo rozpoznanie kodu przez aparat bez otwarcia strony jest niewidoczne. Otwarcie ręcznie skopiowanego linku QR też jest liczone jako wejście QR. Odsłony zawierają również wizyty po QR.

Nie zapisujemy surowego IP ani pełnego User-Agent w bazie. Krótkotrwały HMAC z dzienną składową służy do deduplikacji i pomijania ponownych żądań w ciągu 5 sekund. Jest usuwany po dwóch dniach przez WP-Cron. Dane zagregowane są usuwane według retencji (domyślnie 365 dni). Wartość „odwiedzający” jest przybliżona i liczona na dzień/treść/typ, nie oznacza unikalnych osób w całym okresie. Pomijamy zalogowanych użytkowników mogących edytować daną treść oraz rozpoznane boty. Zmiany ustawień pomiaru uwzględnij w polityce prywatności swojej witryny; sprawdź działanie WP-Cron.

## Rozwój i wydania

Node.js 24 + pnpm 11:

```sh
pnpm install --frozen-lockfile
pnpm build
pnpm check
pnpm test
pnpm test:wordpress
pnpm package
```

ZIP zawiera wyłącznie katalog `qr-pelplin/` z gotowymi assetami. Zależności npm i źródła Vue nie są wymagane na produkcji. CI sprawdza PHP 8.0/8.3, kompiluje assety, wykonuje testy i publikuje release z ZIP-em po zmianie numeru wersji na main. Aktualizator wtyczki korzysta z oficjalnego assetu ZIP z GitHub Releases.

Historia poprzedniej implementacji jest zachowana w Git i na gałęzi archiwalnej; bieżące drzewo main zostało zastąpione nową wtyczką. Istniejące wpisy `qrp_item`, kategorie `qrp_category` i konfiguracja strony z wcześniejszej wersji są zachowywane. Dezaktywacja i usunięcie wtyczki nie kasują treści ani statystyk.

## Licencje i materiały

Kod: GPL-2.0-or-later. Zdjęcie domyślne: Pliszka-GP, [Wikimedia Commons](https://commons.wikimedia.org/wiki/File:Pelplin_-_Katedra_wn%C4%99trze_008GP.jpg), CC BY-SA 4.0; na stronie nakładany jest gradient i kadrowanie CSS. Szczegóły w `qr-pelplin/assets/LICENSES.md`. Domyślny symbol katedry jest autorskim znakiem projektu, nie oficjalnym herbem miasta. Własne logo i fotografie wybierz w CMS.

## Zmiany 1.2.0

PDF w trybie automatycznym otwiera się od razu w portalu przez lokalny PDF.js. Czytnik ma przełączanie stron, numer strony i tekst strony. Ładuje dokument przy pojawieniu się podglądu na ekranie. Jawny tryb pobierania pozostaje opcją; dla zewnętrznego storage należy dopuścić CORS.

W panelu **Pliki i prezentacja** po prawej stronie edytora można dodawać wiele PDF-ów, nagrań, zdjęć, filmów i typowych dokumentów. Kolejne dodania uzupełniają listę; przyciski zmieniają kolejność i usuwają pojedyncze pozycje. Materiały są prezentowane jako podglądy, odtwarzacze lub linki do pobrania.

W panelu **Miejsce na mapie** kliknij **Wybierz miejsce na mapie**, wskaż punkt lub przeciągnij znacznik, wróć do wpisu i zapisz. Mapę można przesuwać również klawiaturą i zatwierdzić środek Enterem. Kafelki kategorii i skróty pod hero prowadzą do stron kategorii z hero i kafelkami treści. Powiązanie skrótu z kategorią wybierzesz w ustawieniach modułów. Po aktualizacji adresy kategorii są automatycznie odświeżane.


## Autorski CMS (1.3.0)

QR Pelplin → Treści QR otwiera własny panel listy i edytor w stylu CMS wtyczki. Zawiera wiele sekcji rich-text, osobną galerię ze wstawianiem zdjęć w wybrane miejsce tekstu, wiele załączników, wybór/utworzenie kategorii, miniaturę, wizualną mapę, status publikacji i generator QR z pobraniem SVG/PNG. Dane pozostają w typie qrp_item WordPressa; istniejące treści i stałe tokeny QR są zachowane. Edytor rich-text korzysta z lokalnego silnika WordPressa, a interfejs i przepływ zapisu są autorskie. Uprawnienia publikacji, przypisanie autorów i sanitacja HTML są sprawdzane na serwerze.

Hero jest krótsze, bez współrzędnych, regionu i numeru sekcji. Kategorie mają jeszcze krótsze hero z tą samą grafiką co miniatura, bez modułu skrótów i bez nagłówka nad wyszukiwarką. Logo nie ma sloganu. Autor zdjęcia domyślnego i licencja są dostępne na stronie Materiały i licencje, zamiast w stopce.

Cztery domyślne grafiki kategorii są realistycznymi ilustracjami AI inspirowanymi Pelplinem, wygenerowanymi przez wbudowane image_gen. Nie przedstawiają dokumentalnych zdjęć ani konkretnych rzeczywistych wydarzeń. Pakiet zawiera zoptymalizowane pliki WebP w qr-pelplin/assets/categories/. Pełne prompty są w docs/category-image-prompts.json. Własne zdjęcia wybrane w CMS mają pierwszeństwo.

## Wpisy i adresy (1.4.1)
Wpisy używają /w/slug/, kategorie /k/slug/, QR /q/token/. Dawne adresy wpisów i kategorii przekierowują, a QR z parametrem qrp_code nadal działa. Galerie mają popup z przewijaniem, załączniki własne nazwy, opisy formatowanie, a mapy pinezki z podglądem historii.

## Kody QR (1.5.0)
Zakładka Kody QR pokazuje wygenerowane kody, ich stałe adresy, cele i pliki SVG/PNG. Administrator może przypisać kod do innej opublikowanej treści bez zmiany tokenu lub wydruku. Statystyki nowych wejść są naliczane aktualnej treści. Lista obejmuje także kody pierwotnie wygenerowane przy szkicach i wpisach w koszu; bez dostępnego celu zwracają 410.

## SEO (1.6.0)
W Wygląd i CMS → SEO i wyszukiwarki ustaw tytuł i opis strony głównej, nazwę portalu, obraz udostępniania, indeksowanie i kod weryfikacji Search Console. Edytor wpisu zawiera własny tytuł, opis i blokadę indeksowania. Wtyczka respektuje ustawienia indeksowania WordPressa oraz obecność popularnych wtyczek SEO. Mapa WordPressa /wp-sitemap.xml pomija chronione i nieindeksowane wpisy QR.

## Media w treści i favicon (1.7.0)
Przycisk Wstaw do tekstu dodaje [qrp_image id="123"] lub [qrp_file id="456"]. Publikacja renderuje te materiały, a pliki wstawione w treść nie są powtarzane pod wpisem. Materiał musi być dołączony do danego wpisu. Favicon wybierzesz w Wygląd i CMS → Nagłówek i hero.

Search Console: usługa Domena wymaga DNS TXT u dostawcy domeny; ustawienia WordPressa tylko przygotowują wartość rekordu. Dla qr.pelplin.pl w strefie pelplin.pl host to qr, a w osobnej strefie qr.pelplin.pl to @. Metatag HTML obsługuje usługę z prefiksem URL. Oficjalna instrukcja: https://support.google.com/webmasters/answer/9008080?hl=pl
