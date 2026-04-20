Erstelle eine minimale, aber funktionale Single‑File‑Webanwendung (index.php), die als konfigurierbares Dashboard für einen Kiosk‑Monitor dient.
Rahmenbedingungen:

Es dürfen nur zwei Dateien existieren:

index.php (enthält PHP, HTML, CSS und JavaScript)
dashboard.yaml (Konfigurationsdatei im selben Ordner)


Keine externen Build‑Tools, alles läuft sofort nach dem Hochladen.

Grundfunktion (erste Version – bewusst simpel):

Aufruf index.php?=dash zeigt das Dashboard ohne Bedienelemente (Kiosk‑Modus).
Standard‑Aufruf ohne Parameter zeigt optional eine einfache Debug- oder Vorschauansicht.

Dashboard‑Features (Basis):

Anzeige eines konfigurierbaren Hintergrunds (Bild oder Farbe).
Anzeige von rotierenden Karten/Slides:

Jede Karte kann enthalten:

ein Bild (z. B. Screenshot einer Webseite)
optional einen Titel
optional einen Link oder iframe‑Inhalt




Karten rotieren automatisch in einem festen Intervall.

Zeitsteuerung (einfacher Start):

Bestimmte Karten oder Hintergründe werden nur zu definierten Uhrzeiten oder Zeiträumen angezeigt.
Zeitlogik wird aus der dashboard.yaml gelesen.

Konfiguration (dashboard.yaml):

Enthält:

globale Einstellungen (Rotation‑Intervall, Übergang)
Hintergrunddefinition
Liste von Karten mit:

Quelle (Bild / URL)
Sichtbarkeits‑Zeiten




YAML wird serverseitig in PHP geladen und interpretiert.

Technische Anforderungen:

Sauber strukturierter, gut lesbarer Code.
Kein Overengineering – basic & funktional starten.
Architektur so aufbauen, dass weitere Features später schrittweise ergänzt werden können (z. B. Animationen, mehrere Layouts, Live‑Reload).

Ziel:

Eine robuste Grundversion, die sofort im Kiosk‑Modus auf einem Monitor laufen kann und als Fundament für spätere Erweiterungen dient.
