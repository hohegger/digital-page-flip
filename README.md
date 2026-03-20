# Digital Page Flip

TYPO3-Extension zum Konvertieren von PDF-Handzetteln in blaetterbare
Online-Kataloge.

|                   |                                              |
| ----------------- | -------------------------------------------- |
| Extension Key     | `digital_page_flip`                          |
| Vendor            | Kit                                          |
| Composer          | `kit/digital-page-flip`                      |
| TYPO3             | 12.4 LTS                                     |
| PHP               | >= 8.2                                       |
| Flipbook-Library  | page-flip (vanilla JS, npm)                  |
| Frontend-Build    | Vite 6 + TypeScript 5 + SCSS (sass)          |
| PDF-Konvertierung | Ghostscript 10.0 + ImageMagick 6.9           |

## Funktionsweise

1. Redakteur legt im TYPO3-Backend einen Flipbook-Record an und
   laedt eine PDF hoch.
2. Beim Speichern konvertiert der `PdfConversionService` die PDF
   automatisch: Ghostscript extrahiert die Seiten als PNG,
   ImageMagick wandelt sie in WebP um.
3. Die generierten Bilder werden im FAL-System abgelegt
   (`fileadmin/user_upload/tx_digitalpageflip/flipbook_<uid>/`).
4. Im Frontend rendert die page-flip Library die Seitenbilder als
   interaktiven Katalog mit realistischem Blaettereffekt.

## Anleitung: Katalog anlegen

### 1. Flipbook-Record erstellen (Backend)

1. Im TYPO3-Backend ins **Listenmodul** wechseln.
2. Eine Seite im Seitenbaum auswaehlen.
3. **Datensatz erstellen** klicken und **Flipbook** waehlen.
4. **Titel** vergeben (z.B. "Handzettel KW 12").
5. Im Feld **PDF-Datei** die PDF hochladen oder aus dem FAL
   auswaehlen.
6. **Speichern** — die Konvertierung startet automatisch.
   Nach Abschluss erscheint eine gruene Meldung:
   *"PDF wurde erfolgreich konvertiert. X Seiten generiert."*
7. Der **Konvertierungsstatus** wechselt auf "Abgeschlossen"
   und die generierten Seiten sind im Tab "Pages" sichtbar.

Falls die Konvertierung fehlschlaegt (rote Meldung), den Status
auf "Ausstehend" setzen und erneut speichern. Alternativ kann
die Konvertierung per CLI ausgeloest werden:

```bash
# Einzelnes Flipbook konvertieren
ddev exec .Build/bin/typo3 digitalpageflip:convert <uid>

# Alle ausstehenden Flipbooks konvertieren
ddev exec .Build/bin/typo3 digitalpageflip:convert
```

### 2. Content Element platzieren (Backend)

1. Zur gewuenschten Seite im **Seitenmodul** wechseln.
2. **Neues Inhaltselement** erstellen.
3. Im Tab **Plugins** den Eintrag **Digital Page Flip** waehlen.
4. Im Feld **Flipbook** den gewuenschten Katalog auswaehlen.
5. **Speichern** — im Frontend wird der Katalog angezeigt.

Wenn kein Flipbook ausgewaehlt wird, zeigt das Plugin eine
Uebersicht aller veroeffentlichten Kataloge.

### 3. Frontend-Ansicht

Der Katalog wird als interaktives Flipbook dargestellt:

- **Cover**: Erste Seite wird einzeln angezeigt.
- **Doppelseiten**: Ab Seite 2 werden jeweils zwei Seiten
  nebeneinander dargestellt (wie ein aufgeschlagenes Heft).
- **Rueckseite**: Letzte Seite wieder einzeln.
- **Navigation**: Vor-/Zurueck-Buttons und Seitenanzeige.
- **Sidebar**: Vertikale Toolbar (Desktop) bzw. horizontale
  Topbar (Tablet/Mobil) mit: Zur ersten Seite, PDF-Download,
  Startseite, Datenschutz und Impressum (konfigurierbar).
- **Tastatur**: Pfeiltasten links/rechts zum Blaettern.
- **Touch**: Wisch-Gesten auf mobilen Geraeten.
- **Mobil**: Einzelseiten-Modus auf kleinen Bildschirmen.

## Asset-Pipeline (Vite)

Die Frontend-Assets (TypeScript + SCSS) werden mit Vite gebaut.

### Quell-Dateien

```
Resources/Private/
├── TypeScript/
│   └── flipbook-init.ts      # page-flip Initialisierung
└── Scss/
    ├── flipbook.scss          # Entry Point (@use imports)
    ├── _tokens.scss           # Design Tokens ($vars + CSS --custom-properties)
    ├── _viewer.scss           # Viewer Hauptkomponente (Layout, Stage, Book)
    ├── _sidebar.scss          # Sidebar / Topbar
    ├── _controls.scss         # Arrow Buttons + Pager
    └── _list.scss             # List View
```

### Build-Output

```
Resources/Public/Build/      # Generiert, committed fuer Distribution
├── .vite/
│   └── manifest.json        # Asset-Mapping (Hash → Datei)
├── js/
│   └── flipbook-[hash].js   # Minifiziertes Bundle
└── assets/
    └── styles-[hash].css    # Minifiziertes CSS
```

### Build ausfuehren

```bash
# In DDEV
ddev build

# Oder manuell
npm ci
npm run build
```

### Wie die Assets eingebunden werden

1. **Vite** baut `flipbook-init.ts` und `flipbook.scss` zu
   gehashten Dateien (z.B. `flipbook-DFlFGxUW.js`).
2. Vite schreibt ein **Manifest** (`.vite/manifest.json`),
   das die Zuordnung Quelldatei → Build-Datei enthaelt.
3. Der **FlipbookController** liest das Manifest und
   registriert die korrekten Pfade ueber den TYPO3
   `AssetCollector`:
   - JS wird als `type="module"` im Footer eingebunden.
   - CSS wird im Head eingebunden.
4. Die Hashes im Dateinamen aendern sich bei jedem Build,
   sodass Browser-Caching automatisch invalidiert wird.

### Konfiguration (vite.config.js)

```javascript
export default defineConfig({
  build: {
    outDir: 'Resources/Public/Build',
    manifest: true,
    rollupOptions: {
      input: {
        flipbook: 'Resources/Private/TypeScript/flipbook-init.ts',
        styles: 'Resources/Private/Scss/flipbook.scss',
      },
    },
  },
});
```

### TypoScript-Konfiguration

Die Flipbook-Darstellung und Sidebar-Links werden ueber
TypoScript-Konstanten gesteuert (editierbar im Constant Editor):

| Konstante                   | Default | Beschreibung            |
| --------------------------- | ------- | ----------------------- |
| `flipbook.width`            | 550     | Seitenbreite (px)       |
| `flipbook.height`           | 880     | Seitenhoehe (px)        |
| `flipbook.showCover`        | 1       | Cover als Einzelseite   |
| `flipbook.swipeDistance`    | 30      | Swipe-Empfindlichkeit   |
| `sidebar.privacyPageUid`   |         | Page UID Datenschutz    |
| `sidebar.imprintPageUid`   |         | Page UID Impressum      |
| `sidebar.homePageUid`      |         | Page UID Startseite     |

Die Flipbook-Werte beziehen sich auf eine **einzelne Seite**.
Im Doppelseiten-Modus verdoppelt sich die angezeigte Breite
automatisch. Sidebar-Links werden nur angezeigt, wenn die
jeweilige Page UID gesetzt ist.

## Lokale Entwicklung (DDEV)

### Voraussetzungen

- [DDEV](https://ddev.readthedocs.io/) (>= 1.23)
- Docker / Colima

### Einrichtung

```bash
# 1. Repository klonen
git clone git@github.com:hohegger/digital-page-flip.git
cd digital-page-flip

# 2. DDEV starten
ddev start

# 3. TYPO3 einrichten (Composer, DB, Admin-User, Extension)
ddev typo3-setup

# 4. Frontend-Assets bauen
ddev build

# 5. Backend oeffnen
ddev launch /typo3
```

**Login:** `admin` / `Password1!`

### DDEV-Befehle

| Befehl               | Beschreibung                              |
| --------------------- | ----------------------------------------- |
| `ddev start`          | Container starten                         |
| `ddev typo3-setup`    | TYPO3 komplett einrichten (einmalig)      |
| `ddev build`          | Frontend-Assets via Vite bauen            |
| `ddev launch /typo3`  | TYPO3-Backend im Browser oeffnen          |
| `ddev ssh`            | Shell im Web-Container                    |
| `ddev stop`           | Container stoppen                         |

### Umgebung

| Service       | Details                        |
| ------------- | ------------------------------ |
| Webserver     | Apache + PHP 8.2 (FPM)        |
| Datenbank     | MySQL 8.0                      |
| Node.js       | 22                             |
| TYPO3 Context | Development                    |
| Document Root | `.Build/public`                |

## Code-Qualitaet

```bash
# Alle Checks ausfuehren
ddev composer ci:all

# Einzeln
ddev composer ci:php:cs       # Code-Style (PER-CS2.0)
ddev composer ci:php:cs:fix   # Code-Style automatisch fixen
ddev composer ci:php:stan     # PHPStan (Level 8)
ddev composer ci:php:rector   # Rector (Deprecation-Check)
ddev composer ci:php:unit     # Unit Tests
```

## Verzeichnisstruktur

```
digital_page_flip/
├── Classes/
│   ├── Command/              # CLI: digitalpageflip:convert
│   ├── Controller/           # Extbase ActionController
│   ├── Domain/Model/         # Flipbook, Page
│   ├── Domain/Repository/    # FlipbookRepository
│   ├── EventListener/        # PSR-14 Event Listener
│   ├── Service/              # PdfConversionService
│   └── ViewHelpers/          # FlipbookDataViewHelper
├── Configuration/
│   ├── TCA/                  # Table Configuration
│   └── TypoScript/           # Setup + Constants
├── Resources/
│   ├── Private/
│   │   ├── Language/         # XLIFF Sprachdateien
│   │   ├── Layouts/          # Fluid Layouts
│   │   ├── Partials/         # Fluid Partials (Sidebar)
│   │   ├── Scss/             # SCSS-Architektur (Vite Entry)
│   │   ├── Templates/        # Fluid Templates
│   │   └── TypeScript/       # page-flip Init (Vite Entry)
│   └── Public/
│       ├── Build/            # Vite-Output (generiert, committed fuer Distribution)
│       └── Icons/            # Extension-Icon
├── Tests/
│   └── Unit/
├── .ddev/                    # DDEV-Konfiguration
├── composer.json
├── ext_emconf.php
├── ext_localconf.php
├── ext_tables.php
├── ext_tables.sql
├── package.json              # npm / Vite
└── vite.config.js
```

## Installation via Composer

```bash
# Repository als Quelle hinzufuegen (composer.json des TYPO3-Projekts)
composer config repositories.digital-page-flip vcs https://github.com/hohegger/digital-page-flip.git

# Dist-Downloads bevorzugen (fuer saubere Pakete ohne Development-Dateien)
composer config preferred-install dist

# Extension installieren
composer require kit/digital-page-flip:^1.0
```

Da das Repository privat ist, wird ein GitHub Token benoetigt:

```bash
composer config github-oauth.github.com <token>
```

Token erstellen unter: https://github.com/settings/tokens/new (Scope: `repo`)

## Lizenz

GPL-2.0-or-later
