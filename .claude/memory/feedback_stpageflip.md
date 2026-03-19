---
name: StPageFlip rendering lessons
description: Critical technical decisions for StPageFlip integration — DOM vs Canvas, density, showCover
type: feedback
---

StPageFlip muss mit DOM-Rendering (loadFromHTML) statt Canvas (loadFromImages) betrieben werden. Canvas-drawImage produziert unscharfe Bilder, native Browser-Skalierung ist deutlich besser.

**Key config:**
- `showCover: true` für erste Seite rechts (Einzelseite), dann Doppelseiten
- Nach loadFromHTML alle Pages per `setDensity('soft')` überschreiben — showCover erzwingt sonst hard auf erster/letzter Seite
- `SizeType` ist KEIN Export der page-flip Library — nicht importieren, `'stretch' as any` verwenden
- maxWidth aus Container-Breite berechnen, nicht aus Konfig-Wert

**Why:** Mehrere Iterationen nötig um scharfe Bildqualität und weiches Blättern zu erreichen. Canvas war der Hauptgrund für Pixeligkeit.

**How to apply:** Bei Änderungen am Flipbook-Frontend immer DOM-Modus beibehalten. Niemals zurück auf loadFromImages wechseln.
