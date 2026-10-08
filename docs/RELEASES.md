# Wydania i aktualizacje QR Pelplin

Po zatwierdzeniu PR do main ustaw wersję w nagłówku qr-pelplin.php i utwórz tag np. v0.1.0 wskazujący na commit w main. GitHub Actions automatycznie zbuduje qr-pelplin.zip i dołączy do GitHub Release. Wersja nagłówka musi być identyczna z tagiem.

Wtyczka odczytuje najnowsze publiczne wydanie przez GitHub API, wyszukuje plik qr-pelplin.zip, integruje aktualizację ze standardowym mechanizmem WordPress i przechowuje wynik przez 6 godzin. W panelu QR Pelplin znajduje się przycisk ręcznego sprawdzania aktualizacji; po sprawdzeniu WordPress odświeża cache aktualizacji. Sama aktualizacja odbywa się standardowym przyciskiem WordPress, po zatwierdzeniu przez administratora.

Repozytorium musi być publiczne, a wydanie opublikowane (nie draft/prerelease). Aktualizacje z prywatnych repozytoriów wymagają innej, uwierzytelnionej metody.

Nie twórz tagu przed sprawdzeniem kodu. Najpierw testuj ZIP na kopii WordPressa.
