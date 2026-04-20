```
Baue ein Dashboard-System mit 3 Dateien im selben Ordner: editor.php, index.php (Kiosk) und data.json (Config). Keine Authentifizierung, keine externen Libraries, keine weiteren Dateien. Der Editor ist ein Baukasten, in dem ich mehrere Seiten (Pages) erstellen kann. Eine Seite ist ein komplettes Set aus Hintergrund, Widget-Anordnung, Widget-Rotation/Playlists und seitenspezifischen Einstellungen. Jede Seite muss separat konfigurierbar sein: Hintergrund-Playlist + Dauer, globale Rotationsregeln, und pro Widget ggf. eigene Playlist + Dauer.
editor.php muss data.json laden (Fallback Default), visuell bearbeiten und wieder speichern (atomar). Der Editor muss: Pages verwalten (anlegen/umbenennen/duplizieren/löschen/reihenfolge), pro Page Widgets verwalten (hinzufügen/duplizieren/löschen), Widgets frei verschieben (Drag), skalieren (Resize-Handles), mehrere Widgets auswählen und gemeinsam bewegen, Z-Index, Styles (Radius/Shadow/Farben/Schrift). Mindestens Widget-Typen: url(iframe), image, text, clock, carousel (Playlist aus url/image mit per-Item duration und optional title). Zusätzlich pro Page: Background playlist (images) mit per-Item duration und Fade.
Es muss eine separate Steuerung geben, welche Seite wann genutzt wird: ein Scheduler mit beliebig vielen Regeln (from/to HH:MM, optional Wochentage), der eine Page aktiviert. Zusätzlich muss es Rotations-Zeitfenster geben: z.B. “Page A aktiv von 08:00–12:00, aber die Carousel-Playlist innerhalb dieser Zeit anders / schneller”. Das bedeutet: Zeitregeln können Page wechseln und/oder Overrides anwenden (Override: Hintergrund-Playlist, Rotation speed, bestimmte Widgets/Playlists). Zeitzone global (z.B. Europe/Berlin).
index.php lädt data.json via fetch("./data.json?ts=..."), bestimmt anhand Zeitzone + Scheduler die aktive Page, rendert deren Hintergrund + Widgets und spielt Rotationen ab. Wenn data.json fehlt/kaputt: sichtbare Fehlermeldung. Wenn iframe geblockt: Hinweis im Widget. Alles muss stabil im Kiosk laufen (optional Auto-Reload alle paar Stunden). Datenmodell in data.json muss eindeutig Pages, Schedules und Overrides abbilden.
Gebe mir nun editor.php und in einem 2. promt index.php aus.(data.json wird generirt.)(Index.php soll nur lesen&anzeigen)
```
```
nun index.php
```
```
erweitere editor.php sodass die einzellnen funktionen funktional werden und weitere seiten settings und generelle settings hinzukommen
```
```
ändere noch das page name festlegbar ist und räume den editor auf (weniger modernes rundes dising und weniger text(admin gui)) und es soll bei html nicht nur iframe sondern auch die option geben das die seite den html holt und diesen dann einbindet. es soll live schonmal vorschau geben von allem wie es hinterher aussieht und aber entsprechend in data.json stehen.
da der quellcode nun sehr groß wird gehen wir von der letzten version (hängt an) aus und du sagst mir welchen block ich durch welchen ersetze sodass eine der sachen die in dem promt gefordert sind umgesetzt werden (und im nächsten promt ändern wir das nächste usw.)
```
