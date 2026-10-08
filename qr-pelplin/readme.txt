=== QR Pelplin ===
Contributors: kaulpl
Tags: qr, city, content, analytics, landing-page
Requires at least: 6.6
Requires PHP: 8.0
Stable tag: 1.3.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Portal miejski QR: ciemny landing page, CMS Vue 3, treści, kategorie, mapa, SVG/PNG i statystyki.

== Installation ==
Upload qr-pelplin.zip in Plugins > Add New. Activate and open QR Pelplin > Wygląd i CMS.
Publish a Treść QR entry before printing its QR code. Exclude ?qrp_code= requests from cache/CDN.

== Changelog ==
= 1.3.0 =
Custom CMS for QR entries: multiple rich-text sections, gallery and inline photos, attachments, categories, thumbnail, visual map and QR generation. Compact heroes and category layout, simpler logo/footer, generated default category illustrations.

= 1.2.0 =
Inline PDF.js viewer, multiple mixed attachments, visual map location picker, category heroes and category links below the main hero. Rewrite rules automatically refresh after plugin upgrades.

= 1.1.0 =
Manual GitHub update checks and native WordPress updater button. Category browsing, minimal mobile entries, municipal QR logo, galleries, PDF/JPG pages and file-only downloads / MP3 player.

= 1.0.0 =
Complete rebuild: configurable portal, local QR generator, stable redirect addresses, attached SVG/PNG files, daily analytics, CSV, media and location support.

== External services ==
OpenStreetMap: public map iframe and editor map tiles are loaded only when the user opens the map. https://www.openstreetmap.org/privacy
GitHub API: periodic release checks for plugin updates. https://docs.github.com/en/site-policy/privacy-policies/github-general-privacy-statement
All Vue, QR, Leaflet and PDF.js dependencies and the default photo are bundled locally. No external QR generation service is used.
