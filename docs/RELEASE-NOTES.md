# QR Pelplin 1.1.0

* Ręczne sprawdzanie najnowszego wydania GitHuba w ustawieniach. Gdy jest dostępne, przycisk aktualizacji uruchamia natywny instalator WordPressa.
* Strony kategorii w layoucie portalu: kafelki treści, przełączniki kategorii, wyszukiwanie w kategorii i paginacja AJAX.
* Pojedyncze wpisy w layoucie portalu na desktopie; na telefonie wąski nagłówek i treść bez rozbudowanego menu/stopki.
* Nowe logo: przekazany znak Pelplina z symbolem QR, w jasnej i złotej kolorystyce strony.
* Panel materiałów przy wpisie: wybór galerii zdjęć, stron PDF/JPG i pliku głównego z biblioteki mediów.
* Tryb automatyczny: treść → strona; sam PDF/JPG → pobranie; sam MP3 → odtwarzacz. Można też ręcznie wybrać prezentację treści, podgląd, pobieranie lub audio.
* Stały QR pozostaje ważny po zmianie pliku lub trybu prezentacji. Wejścia do plików nadal są mierzone.
* Poprawione przekazywanie nonce w linkach aktualizacji i eksportu CSV.

Wymagania: WordPress 6.6+, PHP 8.0+. Aktualizacja zachowuje wpisy, ustawienia i tokeny QR. W wersji 1.0.0 aktualizację wykonaj przez standardowy ekran Wtyczki/aktualizacji lub wgraj nowy ZIP; nowe przyciski pojawiają się od wersji 1.1.0.

Przeglądarka może wymagać dotknięcia Play przy nagraniu MP3. Przy offloadzie plików do storage/CDN nagłówki pobierania należy ustawić również w storage.
