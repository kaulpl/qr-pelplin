# QR Pelplin 1.2.0

* PDF po skanowaniu QR otwiera się domyślnie jako podgląd w portalu, bez wymuszonego pobrania. Lokalny czytnik PDF.js renderuje strony także na telefonach; obsługuje nawigację po stronach i tekst dokumentu.
* Wspólna sekcja wielu załączników: PDF, MP3/audio, zdjęcia, wideo i typowe pliki. Dodawanie kolejnych materiałów, usuwanie i kolejność; każdy materiał otrzymuje właściwy podgląd lub odtwarzacz.
* Wizualny wybór miejsca na mapie Leaflet/OpenStreetMap: kliknięcie, przeciąganie znacznika lub wybór środka mapy. Współrzędne zapisują się automatycznie.
* Strony kategorii z hero, zdjęciem kategorii, skrótami tematycznymi i kafelkami wpisów w layoucie strony głównej.
* Skróty pod hero prowadzą do kategorii, z możliwością wyboru kategorii w CMS.
* Reguły adresów odświeżają się automatycznie przy zmianie wersji wtyczki, także gdy aktualizacja pomija aktywację. Naprawia to 404 pod adresami kategorii po aktualizacji.

Wymagania: WordPress 6.6+, PHP 8.0+. Aktualizacja zachowuje ustawienia, treści i tokeny QR. Jawnie wybrany tryb „Pobierz plik” nadal jest dostępny. PDF z zewnętrznego storage wymaga poprawnych nagłówków CORS do renderowania w przeglądarce.
