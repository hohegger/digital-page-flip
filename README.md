# Digital Page Flip

TYPO3-Extension zum Konvertieren von PDF-Handzetteln in blätterbare
Online-Kataloge.

|                  |                                               |
| ---------------- | --------------------------------------------- |
| Extension Key    | `digital_page_flip`                           |
| Vendor           | Kit                                           |
| Composer         | `kit/digital-page-flip`                       |
| TYPO3            | 12.4 LTS                                      |
| PHP              | >= 8.2                                        |
| Flipbook-Library | StPageFlip (vanilla JS)                       |
| PDF-Konvertierung| Ghostscript 10.0 + ImageMagick 6.9            |

## Funktionsweise

1. Redakteur legt im TYPO3-Backend einen Flipbook-Record an und
   laedt eine PDF hoch.
2. Der `PdfConversionService` extrahiert die Seiten via Ghostscript
   als PNG und konvertiert sie mit ImageMagick zu WebP.
3. Die generierten Bilder werden im FAL-System abgelegt.
4. Im Frontend rendert StPageFlip die Seitenbilder als interaktiven
   Katalog mit realistischem Blaettereffekt.

## Lokale Entwicklung (DDEV)

### Voraussetzungen

- [DDEV](https://ddev.readthedocs.io/) (>= 1.23)
- Docker / Colima

### Einrichtung

```bash
# 1. Repository klonen
git clone git@github.com:<org>/digital-page-flip.git
cd digital-page-flip

# 2. DDEV starten
ddev start

# 3. TYPO3 einrichten (Composer Install, DB, Admin-User, Extension)
ddev typo3-setup

# 4. Frontend-Assets bauen (Vite)
ddev build

# 5. Backend oeffnen
ddev launch /typo3
```

**Login:** `admin` / `Password1!`

### DDEV-Befehle

| Befehl              | Beschreibung                               |
| -------------------- | ------------------------------------------ |
| `ddev start`         | Container starten                          |
| `ddev typo3-setup`   | TYPO3 komplett einrichten (einmalig)       |
| `ddev build`         | Frontend-Assets via Vite bauen             |
| `ddev launch /typo3` | TYPO3-Backend im Browser oeffnen           |
| `ddev ssh`           | Shell im Web-Container                     |
| `ddev stop`          | Container stoppen                          |

### Umgebung

| Service      | Details                         |
| ------------ | ------------------------------- |
| Webserver    | Apache + PHP 8.2 (FPM)         |
| Datenbank    | MySQL 8.0                       |
| Node.js      | 22                              |
| TYPO3 Context| Development                     |
| Document Root| `.Build/public`                 |

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
│   ├── Controller/           # Extbase ActionController
│   ├── Domain/Model/         # Flipbook, Page (Extbase Entities)
│   ├── Domain/Repository/    # FlipbookRepository
│   ├── EventListener/        # PSR-14 Event Listener
│   ├── Service/              # PdfConversionService
│   └── ViewHelpers/          # FlipbookDataViewHelper
├── Configuration/
│   ├── FlexForms/            # Plugin-Konfiguration
│   ├── TCA/                  # Table Configuration
│   └── TypoScript/           # Setup + Constants
├── Resources/
│   ├── Private/
│   │   ├── Css/              # Quell-CSS
│   │   ├── Language/         # XLIFF Sprachdateien
│   │   ├── Layouts/          # Fluid Layouts
│   │   ├── Partials/         # Fluid Partials
│   │   ├── Templates/        # Fluid Templates
│   │   └── TypeScript/       # StPageFlip Init (Vite Entry)
│   └── Public/
│       ├── Build/            # Vite-Output (generiert)
│       └── Icons/            # Extension-Icon
├── Tests/
│   ├── Unit/
│   └── Functional/
├── .ddev/                    # DDEV-Konfiguration
├── composer.json
├── ext_emconf.php
├── ext_localconf.php
├── ext_tables.sql
├── package.json              # npm/Vite
└── vite.config.js
```

## Lizenz

GPL-2.0-or-later
