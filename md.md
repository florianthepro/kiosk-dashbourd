```
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
```
```
Baue ein Dashboard-System mit 3 Dateien im selben Ordner: editor.php, index.html (Kiosk) und data.json (Config). Keine Authentifizierung, keine externen Libraries, keine weiteren Dateien. Der Editor ist ein Baukasten, in dem ich mehrere Seiten (Pages) erstellen kann. Eine Seite ist ein komplettes Set aus Hintergrund, Widget-Anordnung, Widget-Rotation/Playlists und seitenspezifischen Einstellungen. Jede Seite muss separat konfigurierbar sein: Hintergrund-Playlist + Dauer, globale Rotationsregeln, und pro Widget ggf. eigene Playlist + Dauer.
editor.php muss data.json laden (Fallback Default), visuell bearbeiten und wieder speichern (atomar). Der Editor muss: Pages verwalten (anlegen/umbenennen/duplizieren/löschen/reihenfolge), pro Page Widgets verwalten (hinzufügen/duplizieren/löschen), Widgets frei verschieben (Drag), skalieren (Resize-Handles), mehrere Widgets auswählen und gemeinsam bewegen, Z-Index, Styles (Radius/Shadow/Farben/Schrift). Mindestens Widget-Typen: url(iframe), image, text, clock, carousel (Playlist aus url/image mit per-Item duration und optional title). Zusätzlich pro Page: Background playlist (images) mit per-Item duration und Fade.
Es muss eine separate Steuerung geben, welche Seite wann genutzt wird: ein Scheduler mit beliebig vielen Regeln (from/to HH:MM, optional Wochentage), der eine Page aktiviert. Zusätzlich muss es Rotations-Zeitfenster geben: z.B. “Page A aktiv von 08:00–12:00, aber die Carousel-Playlist innerhalb dieser Zeit anders / schneller”. Das bedeutet: Zeitregeln können Page wechseln und/oder Overrides anwenden (Override: Hintergrund-Playlist, Rotation speed, bestimmte Widgets/Playlists). Zeitzone global (z.B. Europe/Berlin).
index.html lädt data.json via fetch("./data.json?ts=..."), bestimmt anhand Zeitzone + Scheduler die aktive Page, rendert deren Hintergrund + Widgets und spielt Rotationen ab. Wenn data.json fehlt/kaputt: sichtbare Fehlermeldung. Wenn iframe geblockt: Hinweis im Widget. Alles muss stabil im Kiosk laufen (optional Auto-Reload alle paar Stunden). Datenmodell in data.json muss eindeutig Pages, Schedules und Overrides abbilden.
