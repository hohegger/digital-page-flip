# Digital Page Flip — Extension-Architektur

**TYPO3-Extension: `digital_page_flip`**
**Namespace: `\Kit\DigitalPageFlip`**
**Stand:** 18. März 2026

---

## 1. Verzeichnisstruktur

```
digital_page_flip/
├── .claude/
│   └── rules/
│       ├── typo3-conventions.md       # TYPO3-spezifische Regeln
│       ├── php-code-style.md          # PHP Coding Standards
│       ├── security.md                # Sicherheitsrichtlinien
│       ├── frontend.md                # Frontend/JS/CSS Regeln
│       ├── testing.md                 # Test-Konventionen
│       └── git-workflow.md            # Git & Commit Regeln
├── Classes/
│   ├── Controller/
│   │   └── FlipbookController.php          # Frontend-Plugin (list, show)
│   ├── Domain/
│   │   ├── Model/
│   │   │   ├── Flipbook.php                # Hauptentität (Katalog)
│   │   │   └── Page.php                    # Einzelseite (generiertes Bild)
│   │   └── Repository/
│   │       └── FlipbookRepository.php      # Datenzugriff
│   ├── Service/
│   │   └── PdfConversionService.php        # Ghostscript PDF→Bild Konvertierung
│   ├── EventListener/
│   │   └── AfterFileAddedListener.php      # Optional: Auto-Konvertierung nach Upload
│   └── ViewHelpers/
│       └── FlipbookDataViewHelper.php      # JSON-Daten für StPageFlip
├── Configuration/
│   ├── TCA/
│   │   ├── tx_digitalpageflip_domain_model_flipbook.php
│   │   ├── tx_digitalpageflip_domain_model_page.php
│   │   └── Overrides/
│   │       └── tt_content.php              # Content Element registrieren
│   ├── TypoScript/
│   │   ├── setup.typoscript
│   │   └── constants.typoscript
│   ├── FlexForms/
│   │   └── Flipbook.xml                    # Plugin-Konfiguration (welcher Katalog?)
│   ├── Icons.php                           # Icon-Registry
│   └── Services.yaml                       # Dependency Injection
├── Resources/
│   ├── Private/
│   │   ├── Language/
│   │   │   ├── locallang.xlf               # Frontend-Labels
│   │   │   └── locallang_db.xlf            # Backend-Labels (TCA)
│   │   ├── Layouts/
│   │   │   └── Default.html
│   │   ├── Partials/
│   │   │   └── Flipbook/
│   │   │       └── Canvas.html             # StPageFlip Container
│   │   ├── Templates/
│   │   │   └── Flipbook/
│   │   │       ├── List.html               # Katalog-Übersicht
│   │   │       └── Show.html               # Einzelner Flipbook-Viewer
│   │   ├── TypeScript/
│   │   │   └── flipbook-init.ts            # Einstiegspunkt (importiert page-flip)
│   │   └── Css/
│   │       └── flipbook.css                # Quell-CSS
│   └── Public/
│       ├── Build/                           # Vite-Output (generiert)
│       │   ├── .vite/
│       │   │   └── manifest.json            # Asset-Mapping für TYPO3
│       │   ├── js/
│       │   │   └── flipbook-[hash].js       # Minifiziertes Bundle
│       │   └── assets/
│       │       └── flipbook-[hash].css      # Minifiziertes CSS
│       └── Icons/
│           └── Extension.svg               # Extension-Icon
├── Tests/
│   ├── Unit/
│   │   └── Service/
│   │       └── PdfConversionServiceTest.php
│   └── Functional/
│       └── Repository/
│           └── FlipbookRepositoryTest.php
├── .editorconfig                          # Code-Formatierung (TYPO3 CGL / PSR-12)
├── .php-cs-fixer.php                      # PHP-CS-Fixer Konfiguration
├── phpstan.neon                           # PHPStan Konfiguration
├── rector.php                             # Rector Konfiguration
├── phpunit.xml                            # PHPUnit Konfiguration
├── CLAUDE.md                              # KI-Projektkontext (Claude Code)
├── ext_emconf.php
├── ext_localconf.php
├── ext_tables.sql
└── composer.json
```

---

## 2. Domain Model

### 2.1 Flipbook (Hauptentität)

Repräsentiert einen einzelnen Katalog/Handzettel.

**Datenbank-Tabelle:** `tx_digitalpageflip_domain_model_flipbook`

| Feld | Typ | Beschreibung |
|---|---|---|
| `title` | varchar(255) | Titel des Katalogs (z.B. "KW 12 — Frühlingsangebote") |
| `description` | text | Optionale Beschreibung |
| `pdf_file` | int (FAL) | Referenz auf die Original-PDF |
| `pages` | int | IRRE-Relation zu Page-Records (generierte Seitenbilder) |
| `page_count` | int | Anzahl der Seiten (nach Konvertierung gesetzt) |
| `conversion_status` | int | 0=pending, 1=processing, 2=completed, 3=error |
| `publish_date` | datetime | Veröffentlichungsdatum |
| `hidden` | tinyint | Sichtbarkeit |
| `starttime` | int | Zeitgesteuerte Veröffentlichung |
| `endtime` | int | Zeitgesteuertes Ablaufdatum |

### 2.2 Page (Einzelseite)

Repräsentiert eine einzelne konvertierte Seite als Bild.

**Datenbank-Tabelle:** `tx_digitalpageflip_domain_model_page`

| Feld | Typ | Beschreibung |
|---|---|---|
| `flipbook` | int | Fremdschlüssel zum Flipbook-Record |
| `page_number` | int | Seitennummer (1-basiert) |
| `image` | int (FAL) | Referenz auf das generierte Seitenbild (WebP) |
| `image_fallback` | int (FAL) | Referenz auf PNG-Fallback |
| `sorting` | int | Sortierung |

### 2.3 Entity-Relationship

```
┌─────────────────┐         ┌─────────────────┐
│    Flipbook      │ 1    n │      Page         │
│─────────────────│─────────│─────────────────│
│ title            │         │ page_number      │
│ description      │         │ image (FAL)      │
│ pdf_file (FAL)   │         │ image_fallback   │
│ page_count       │         │ sorting          │
│ conversion_status│         │ flipbook (FK)    │
│ publish_date     │         └─────────────────┘
└─────────────────┘
         │
         │ 1
         ▼
   ┌───────────┐
   │  PDF (FAL) │
   └───────────┘
```

---

## 3. Kern-Klassen

### 3.1 PdfConversionService

Zentrale Service-Klasse für die PDF-zu-Bild-Konvertierung.

**Verantwortlichkeiten:**

- PDF-Validierung (MIME-Type, Dateigröße)
- Ghostscript-Aufruf mit Sandbox-Modus (`-dSAFER`)
- Seitenweise Konvertierung in WebP + PNG-Fallback
- Registrierung der generierten Bilder im FAL-System
- Erstellung der Page-Records mit Zuordnung zum Flipbook
- Status-Updates am Flipbook-Record (pending → processing → completed/error)

**Ghostscript-Kommando (Konzept):**

```bash
gs -dSAFER -dBATCH -dNOPAUSE \
   -sDEVICE=png16m \
   -r150 \
   -dFirstPage=1 -dLastPage=1 \
   -sOutputFile=page_%03d.png \
   input.pdf
```

Anschließend Konvertierung zu WebP via ImageMagick:

```bash
convert page_001.png -quality 85 page_001.webp
```

**Sicherheitsmaßnahmen:**

- `-dSAFER` verhindert Dateisystemzugriff durch die PDF
- MIME-Type-Validierung vor Verarbeitung (`application/pdf`)
- Temporäres Verzeichnis für Konvertierung, danach Cleanup
- Maximale Dateigröße konfigurierbar
- Timeout für Ghostscript-Prozess

### 3.2 FlipbookController

Extbase ActionController für das Frontend-Plugin.

**Actions:**

| Action | Beschreibung |
|---|---|
| `listAction()` | Zeigt alle veröffentlichten Flipbooks (optional, falls Übersicht gewünscht) |
| `showAction(Flipbook $flipbook)` | Rendert den Flipbook-Viewer mit StPageFlip |

### 3.3 FlipbookRepository

Standard Extbase Repository mit optionalen Custom-Queries.

**Methoden:**

- `findPublished()` — Nur Flipbooks mit `conversion_status = 2` und gültigem Zeitfenster
- `findByUid()` — Standard (geerbt)

---

## 4. Asset-Pipeline & StPageFlip Integration

### 4.1 Integrationsstrategie

**Gewählt: npm + Vite Build-Pipeline**

| Ansatz | Vorteile | Nachteile |
|---|---|---|
| ~~Vendor (manuell kopieren)~~ | Kein Build-Tooling nötig | Kein automatisches Update, kein Tree-Shaking |
| **npm + Vite** ✅ | Versionierung, Tree-Shaking, Minification, Hashing | Build-Step nötig |

**Begründung:** Da wir ohnehin eine `.editorconfig`, QA-Tools und eine moderne Extension-Architektur haben, ist ein Build-Step kein Overhead sondern Standard. Vite liefert uns minifiziertes, gehashtes JS/CSS und ist in TYPO3 v14 der empfohlene Weg (v14 hat CSS/JS-Concatenation entfernt, siehe #108055).

### 4.2 npm-Abhängigkeiten

```json
{
  "name": "digital-page-flip",
  "private": true,
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview"
  },
  "devDependencies": {
    "vite": "^6.0",
    "typescript": "^5.5"
  },
  "dependencies": {
    "page-flip": "^2.0.7"
  }
}
```

**StPageFlip (npm: `page-flip`):**

- Version: 2.0.7
- Keine eigenen Abhängigkeiten (zero dependencies)
- Liefert ES Module (`dist/js/page-flip.module.js`) und Browser-Bundle (`dist/js/page-flip.browser.js`)
- Wir nutzen den ES-Module-Import → Vite erledigt Tree-Shaking und Bundling

### 4.3 Vite-Konfiguration

```javascript
// vite.config.js
import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    outDir: 'Resources/Public/Build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        flipbook: 'Resources/Private/TypeScript/flipbook-init.ts',
        styles: 'Resources/Private/Css/flipbook.css',
      },
      output: {
        entryFileNames: 'js/[name]-[hash].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash][extname]',
      },
    },
  },
});
```

### 4.4 Verzeichnisstruktur (Assets)

```
Resources/
├── Private/
│   ├── TypeScript/
│   │   └── flipbook-init.ts         # Einstiegspunkt (importiert page-flip)
│   └── Css/
│       └── flipbook.css             # Quell-CSS
└── Public/
    └── Build/                        # Vite-Output (gitignored!)
        ├── .vite/
        │   └── manifest.json         # Asset-Mapping für TYPO3
        ├── js/
        │   └── flipbook-[hash].js    # Minifiziertes Bundle (StPageFlip + Init)
        └── assets/
            └── flipbook-[hash].css   # Minifiziertes CSS
```

### 4.5 TypeScript-Einstiegspunkt

```typescript
// Resources/Private/TypeScript/flipbook-init.ts
import { PageFlip } from 'page-flip';

interface FlipbookConfig {
  width: number;
  height: number;
  showCover: boolean;
  mobileScrollSupport: boolean;
  swipeDistance: number;
}

document.querySelectorAll<HTMLElement>('.digital-page-flip').forEach((container) => {
  const pages: string[] = JSON.parse(container.dataset.flipbookPages || '[]');
  const config: FlipbookConfig = JSON.parse(container.dataset.flipbookConfig || '{}');

  const pageFlip = new PageFlip(container, {
    width: config.width,
    height: config.height,
    showCover: config.showCover,
    mobileScrollSupport: config.mobileScrollSupport,
    swipeDistance: config.swipeDistance,
    maxShadowOpacity: 0.5,
    autoSize: true,
  });

  pageFlip.loadFromImages(pages);
});
```

### 4.6 TYPO3 Asset-Einbindung

**Option A — Vite Asset Collector Extension (empfohlen):**

```bash
composer require praetorius/vite-asset-collector
```

```html
<!-- Fluid Template -->
<vac:asset.vite
    manifest="EXT:digital_page_flip/Resources/Public/Build/.vite/manifest.json"
    entry="Resources/Private/TypeScript/flipbook-init.ts" />
```

**Option B — Manuelle Einbindung (Production-Build):**

```typoscript
page.includeCSS {
    digitalPageFlip = EXT:digital_page_flip/Resources/Public/Build/assets/flipbook-[hash].css
}
page.includeJSFooter {
    digitalPageFlip = EXT:digital_page_flip/Resources/Public/Build/js/flipbook-[hash].js
    digitalPageFlip.type = module
    digitalPageFlip.defer = 1
}
```

**Option C — AssetCollector im Controller (programmatisch):**

```php
use TYPO3\CMS\Core\Page\AssetCollector;

final class FlipbookController extends ActionController
{
    public function __construct(
        private readonly AssetCollector $assetCollector,
    ) {}

    public function showAction(Flipbook $flipbook): ResponseInterface
    {
        $this->assetCollector->addJavaScript(
            'digital-page-flip',
            'EXT:digital_page_flip/Resources/Public/Build/js/flipbook.js',
            ['type' => 'module', 'defer' => 'defer']
        );
        $this->assetCollector->addStyleSheet(
            'digital-page-flip',
            'EXT:digital_page_flip/Resources/Public/Build/assets/flipbook.css'
        );

        $this->view->assign('flipbook', $flipbook);
        return $this->htmlResponse();
    }
}
```

### 4.7 Build-Workflow

```bash
# Entwicklung (HMR)
npm run dev

# Production-Build
npm run build

# Ergebnis in Resources/Public/Build/ → committen oder CI/CD
```

**CI/CD-Integration:**

```bash
npm ci && npm run build
```

Das Build-Verzeichnis `Resources/Public/Build/` kann entweder commited oder via CI/CD generiert werden. Empfehlung: Für eine Extension die über Packagist verteilt wird → Build committen. Für ein internes Projekt → CI/CD generiert.

---

## 5. Frontend-Rendering

### 5.1 Fluid Template (Show.html) — Konzept

```html
<div class="digital-page-flip"
     id="flipbook-{flipbook.uid}"
     data-flipbook-pages="{kit:flipbookData(flipbook: flipbook)}"
     data-flipbook-config='{
       "width": 800,
       "height": 600,
       "showCover": true,
       "mobileScrollSupport": true,
       "swipeDistance": 30
     }'>
    <!-- StPageFlip rendert hier den Canvas -->
</div>
```

### 5.2 CSS-Konzept

Minimal, neutral, nur das Nötigste:

```css
.digital-page-flip {
    max-width: 100%;
    margin: 0 auto;
    aspect-ratio: 4 / 3; /* Anpassbar per TypoScript-Konstante */
}

@media (max-width: 768px) {
    .digital-page-flip {
        aspect-ratio: auto; /* Mobile: Einzelseiten-Modus */
    }
}
```

---

## 6. Backend-Integration

### 6.1 Content Element

Die Extension registriert ein **Content Element** (`CType`), das Redakteure auf jeder Seite platzieren können. Über ein FlexForm wählt der Redakteur den gewünschten Flipbook-Record aus.

**Workflow für Redakteure:**

1. Neuen Flipbook-Record anlegen (Listenmodul oder eigener Tab)
2. Titel vergeben, PDF hochladen
3. Konvertierung wird automatisch angestoßen (via DataHandler-Hook oder manueller Button)
4. Content Element "Digital Page Flip" auf der gewünschten Seite einfügen
5. Im FlexForm den Flipbook-Record auswählen
6. Fertig — im Frontend wird der blätterbare Katalog angezeigt

### 6.2 TCA-Highlights

**Flipbook-Record:**

- PDF-Upload als FAL-Feld (`type: file`, `allowed: pdf`)
- Status-Anzeige als Select-Feld (read-only im Backend)
- IRRE-Relation zu den generierten Pages (read-only, automatisch befüllt)
- Publish-Date als Datetime-Feld

**Page-Records:**

- Werden automatisch durch den `PdfConversionService` erstellt
- Im Backend nur als IRRE-Kinder des Flipbook-Records sichtbar
- Nicht manuell editierbar (sortierung ausgenommen)

---

## 7. TypoScript-Konfiguration

### 7.1 Konstanten (constants.typoscript)

```typoscript
plugin.tx_digitalpageflip {
    settings {
        # Ghostscript-Pfad (falls nicht im PATH)
        ghostscriptPath = /usr/bin/gs

        # Bildqualität
        imageResolution = 150
        webpQuality = 85

        # Maximale PDF-Dateigröße in MB
        maxFileSize = 50

        # StPageFlip Defaults
        flipbook.width = 800
        flipbook.height = 600
        flipbook.showCover = 1
        flipbook.swipeDistance = 30
    }
}
```

---

## 8. Dependency Injection (Services.yaml)

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true
    public: false

  Kit\DigitalPageFlip\:
    resource: '../Classes/*'

  Kit\DigitalPageFlip\Service\PdfConversionService:
    arguments:
      $ghostscriptPath: '%env(GHOSTSCRIPT_PATH)%'
```

---

## 9. Datenbank-Schema (ext_tables.sql)

```sql
CREATE TABLE tx_digitalpageflip_domain_model_flipbook (
    title varchar(255) DEFAULT '' NOT NULL,
    description text,
    pdf_file int(11) unsigned DEFAULT '0' NOT NULL,
    pages int(11) unsigned DEFAULT '0' NOT NULL,
    page_count int(11) DEFAULT '0' NOT NULL,
    conversion_status int(11) DEFAULT '0' NOT NULL,
    publish_date datetime DEFAULT NULL,
);

CREATE TABLE tx_digitalpageflip_domain_model_page (
    flipbook int(11) DEFAULT '0' NOT NULL,
    page_number int(11) DEFAULT '0' NOT NULL,
    image int(11) unsigned DEFAULT '0' NOT NULL,
    image_fallback int(11) unsigned DEFAULT '0' NOT NULL,
    sorting int(11) DEFAULT '0' NOT NULL,
);
```

---

## 10. Konvertierungs-Workflow (Sequenzdiagramm)

```
Redakteur          TYPO3 Backend        PdfConversionService        Ghostscript / ImageMagick
    │                    │                        │                          │
    │  PDF hochladen     │                        │                          │
    │───────────────────>│                        │                          │
    │                    │  Flipbook-Record        │                          │
    │                    │  erstellen (status=0)   │                          │
    │                    │───────────────────────>│                          │
    │                    │                        │  Status → processing (1)  │
    │                    │                        │                          │
    │                    │                        │  gs -dSAFER ...          │
    │                    │                        │─────────────────────────>│
    │                    │                        │  PNG pro Seite           │
    │                    │                        │<─────────────────────────│
    │                    │                        │                          │
    │                    │                        │  convert → WebP          │
    │                    │                        │─────────────────────────>│
    │                    │                        │  WebP-Dateien            │
    │                    │                        │<─────────────────────────│
    │                    │                        │                          │
    │                    │                        │  Bilder in FAL           │
    │                    │                        │  registrieren            │
    │                    │                        │                          │
    │                    │                        │  Page-Records erstellen  │
    │                    │                        │                          │
    │                    │                        │  Status → completed (2)  │
    │                    │  Flipbook bereit       │                          │
    │                    │<───────────────────────│                          │
    │  Bestätigung       │                        │                          │
    │<───────────────────│                        │                          │
```

---

## 11. Sicherheitsarchitektur

| Maßnahme | Implementierung |
|---|---|
| PDF-Validierung | MIME-Check (`application/pdf`), Dateigrößenlimit, keine Ausführung |
| Ghostscript Sandbox | `-dSAFER -dBATCH -dNOPAUSE` Flags |
| Prozess-Timeout | `proc_open()` mit Timeout, verhindert Endlosschleifen |
| FAL-Zugriff | Keine direkte Dateisystem-Exposition, alles über TYPO3 FAL |
| CSRF-Schutz | TYPO3 Backend FormProtection für alle Aktionen |
| SQL-Injection | Extbase QueryBuilder mit Named Parameters |
| XSS-Prevention | Fluid Auto-Escaping, kein ungefilterter Output |
| Kein Frontend-Upload | Angriffsfläche auf authentifizierte BE-User beschränkt |

---

## 12. v12 → v14 Migrationspfad

| Aspekt | v12 (Aktuell) | v14 (Ziel) | Aufwand |
|---|---|---|---|
| PHP Namespace | `\Kit\DigitalPageFlip` | Bleibt gleich | Keiner |
| Extbase/Fluid | Standard MVC | Fluid 5 Anpassungen | Gering |
| Events | PSR-14 | Bleibt gleich | Keiner |
| DI | Services.yaml | Bleibt gleich | Keiner |
| TCA | v12 Syntax | Minimalanpassungen | Gering |
| Assets | AssetCollector | System Resources API prüfen | Gering |
| Composer | `typo3/cms-core: ^12.4` | `typo3/cms-core: ^14.0` | Constraint-Update |

**Erwarteter Gesamtaufwand v14-Migration:** Minimal — primär Constraint-Update + Testing

---

## 13. Code-Qualität & Tooling

### 13.1 .editorconfig

Basierend auf dem offiziellen [TYPO3 Coding Standards Package](https://github.com/TYPO3/coding-standards) (`typo3/coding-standards`). Stellt sicher, dass alle Entwickler und KI-Tools (Claude Code) identische Formatierung verwenden — unabhängig vom Editor.

**Quelle:** `composer require --dev typo3/coding-standards` generiert die Datei automatisch. Die folgende Konfiguration entspricht dem TYPO3-Standard, ergänzt um projektspezifische Regeln für die Extension.

```editorconfig
# EditorConfig: https://editorconfig.org
# Basiert auf TYPO3 Coding Standards (PSR-12 / PER-CS2.0)

root = true

# Globale Defaults
[*]
charset = utf-8
end_of_line = lf
indent_style = space
indent_size = 4
insert_final_newline = true
trim_trailing_whitespace = true

# TypeScript / JavaScript (StPageFlip Init, flipbook-init.js)
[*.{ts,js}]
indent_size = 2

# JSON (composer.json, package.json)
[*.json]
indent_style = tab

# package.json (Ausnahme: Spaces)
[package.json]
indent_size = 2
indent_style = space

# YAML (Services.yaml, config.yaml)
[*.{yaml,yml}]
indent_size = 2

# NEON (phpstan.neon)
[*.neon]
indent_size = 2
indent_style = tab

# TypoScript
[*.{typoscript,tsconfig}]
indent_size = 2

# XLF Sprachdateien
[*.xlf]
indent_style = tab

# SQL (ext_tables.sql)
[*.sql]
indent_style = tab
indent_size = 2

# Fluid Templates (HTML)
[*.html]
indent_size = 4

# Markdown (Dokumentation, CLAUDE.md)
[*.md]
max_line_length = 80

# ReST Dokumentation
[*.{rst,rst.txt}]
indent_size = 4
max_line_length = 80

# .htaccess
[{_.htaccess,.htaccess}]
indent_style = tab

# CSS
[*.css]
indent_size = 4
```

### 13.2 QA-Toolchain

Die Extension nutzt das vollständige TYPO3 QA-Toolset. Die Konfiguration wird mit dem Projekt ausgeliefert:

| Datei | Tool | Zweck |
|---|---|---|
| `.editorconfig` | EditorConfig | Einheitliche Formatierung über alle Editoren |
| `.php-cs-fixer.php` | PHP-CS-Fixer | PSR-12 / TYPO3 CGL Code-Style |
| `phpstan.neon` | PHPStan | Statische Analyse (Level 8) |
| `rector.php` | Rector | Automatische Migration & Refactoring |
| `phpunit.xml` | PHPUnit | Unit- & Functional Tests |

**Composer-Dependencies (dev):**

```json
{
    "require-dev": {
        "typo3/coding-standards": "^0.8",
        "phpstan/phpstan": "^2.0",
        "saschaegerer/phpstan-typo3": "^2.0",
        "ssch/typo3-rector": "^2.0",
        "phpunit/phpunit": "^11.0",
        "typo3/testing-framework": "^9.0"
    }
}
```

**Composer-Scripts:**

```json
{
    "scripts": {
        "ci:php:cs": "php-cs-fixer fix --dry-run --diff",
        "ci:php:cs:fix": "php-cs-fixer fix",
        "ci:php:stan": "phpstan analyse --memory-limit=2G",
        "ci:php:rector": "rector process --dry-run",
        "ci:php:unit": "phpunit --testsuite Unit",
        "ci:php:functional": "phpunit --testsuite Functional",
        "ci:all": [
            "@ci:php:cs",
            "@ci:php:stan",
            "@ci:php:rector",
            "@ci:php:unit"
        ]
    }
}
```
