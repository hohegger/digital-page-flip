# Digital Page Flip — KI-Integration (Claude Code)

**TYPO3-Extension: `digital_page_flip`**
**Stand:** 18. März 2026

---

## 1. Übersicht

Dieses Dokument beschreibt die Integration von Claude Code in den Entwicklungsworkflow der Extension. Ziel ist es, durch eine gut strukturierte `CLAUDE.md` und modulare `.claude/rules/`-Dateien sicherzustellen, dass Claude bei jeder Session den Projektkontext versteht und konsistent hochwertigen Code produziert.

---

## 2. Verzeichnisstruktur

```
digital_page_flip/
├── CLAUDE.md                          # Hauptinstruktionen (< 200 Zeilen)
├── .claude/
│   └── rules/
│       ├── typo3-conventions.md       # TYPO3-spezifische Regeln
│       ├── php-code-style.md          # PHP Coding Standards
│       ├── security.md                # Sicherheitsrichtlinien
│       ├── frontend.md                # Frontend/JS/CSS Regeln
│       ├── testing.md                 # Test-Konventionen
│       └── git-workflow.md            # Git & Commit Regeln
├── Classes/
├── Configuration/
├── Resources/
├── Tests/
└── ...
```

---

## 3. CLAUDE.md — Hauptdatei

Die `CLAUDE.md` liegt im Projekt-Root und wird bei jedem Session-Start geladen. Sie enthält die wichtigsten Eckpunkte — alles Weitere wird in `.claude/rules/` ausgelagert.

**Zielumfang:** Unter 200 Zeilen.

### Geplanter Inhalt:

```markdown
# Digital Page Flip — TYPO3 Extension

## Projekt
TYPO3-Extension zum Konvertieren von PDF-Handzetteln in blätterbare Online-Kataloge.
Vendor: Kit | Extension Key: digital_page_flip | Namespace: \Kit\DigitalPageFlip

## Tech-Stack
- TYPO3 v12.4 LTS (v14-ready)
- PHP 8.2 (strict_types, readonly, final)
- Extbase + Fluid (MVC)
- StPageFlip (vanilla JS Flipbook-Library)
- Ghostscript 10.0 + ImageMagick 6.9 (PDF→WebP/PNG Konvertierung)

## Build & Test
- PHP-CS-Fixer: vendor/bin/php-cs-fixer fix --dry-run --diff
- PHPStan: vendor/bin/phpstan analyse
- Rector: vendor/bin/rector process --dry-run
- PHPUnit: vendor/bin/phpunit --testsuite Unit
- Vor jedem Commit: composer ci:php:cs:fix && composer ci:php:stan && composer ci:php:unit

## Architektur-Regeln
- PSR-14 Events statt Legacy-Hooks
- Dependency Injection durchgängig (Services.yaml)
- Keine deprecated TYPO3 APIs verwenden
- Alle Klassen `final` und `declare(strict_types=1)`
- QueryBuilder mit Named Parameters (nie SQL konkatenieren)
- FAL für alle Dateioperationen

## Verzeichnisstruktur
- Classes/Controller/ → Extbase ActionController
- Classes/Domain/Model/ → Extbase Entities
- Classes/Domain/Repository/ → Extbase Repositories
- Classes/Service/ → Business Logic (PdfConversionService)
- Configuration/TCA/ → Table Configuration
- Resources/Private/Templates/ → Fluid Templates
- Resources/Public/JavaScript/ → StPageFlip + Init-Script

## Wichtige Dateien
- Classes/Service/PdfConversionService.php → Kernlogik PDF-Konvertierung
- Resources/Public/JavaScript/flipbook-init.js → StPageFlip Initialisierung
- Configuration/TCA/tx_digitalpageflip_domain_model_flipbook.php → Haupt-TCA
- ext_tables.sql → Datenbank-Schema

## Sicherheit
- Ghostscript immer mit -dSAFER -dBATCH -dNOPAUSE
- PDF MIME-Type validieren vor Verarbeitung
- Kein Frontend-Upload, nur Backend (authentifizierte BE-User)
- XSS: Fluid Auto-Escaping, htmlspecialchars() für manuellen Output
- CSRF: TYPO3 FormProtection für alle Backend-Aktionen
```

---

## 4. `.claude/rules/` — Modulare Regeldateien

### 4.1 `typo3-conventions.md`

Wird bei allen PHP-Dateien unter `Classes/` geladen.

```markdown
---
paths:
  - "Classes/**/*.php"
  - "Configuration/**/*.php"
  - "ext_localconf.php"
  - "ext_emconf.php"
---

# TYPO3 Konventionen

## Namespace
- Vendor: Kit
- Extension: DigitalPageFlip
- Pattern: \Kit\DigitalPageFlip\{Category}\{ClassName}

## Extbase
- Controller: final class, extends ActionController
- Actions geben ResponseInterface zurück (return $this->htmlResponse())
- Models: AbstractEntity, getter/setter, typisierte Properties
- Repositories: extends Repository, Custom Queries via QueryBuilder

## TCA
- Labels über LLL:EXT:digital_page_flip/Resources/Private/Language/locallang_db.xlf
- ctrl-Sektion: immer tstamp, crdate, delete, enablecolumns
- FAL-Felder: type => 'file', allowed => 'pdf' bzw. 'common-image-types'

## Plugin-Registrierung
- ext_localconf.php: ExtensionUtility::configurePlugin()
- PLUGIN_TYPE_CONTENT_ELEMENT verwenden
- FlexForm für Plugin-Konfiguration

## v14-Kompatibilität
- Keine @annotations für Extbase, nur PHP Attributes vorbereiten
- Kein TypoScriptFrontendController direkt verwenden
- AssetCollector für CSS/JS Einbindung
```

### 4.2 `php-code-style.md`

```markdown
---
paths:
  - "Classes/**/*.php"
  - "Tests/**/*.php"
---

# PHP Code Style

## Grundregeln
- PSR-12 + TYPO3 CGL
- declare(strict_types=1) in jeder Datei
- UTF-8 ohne BOM
- 4 Spaces Einrückung
- Klassen-Klammern: nächste Zeile
- Kontrollstruktur-Klammern: gleiche Zeile

## Klassen
- final class als Standard
- readonly Properties wo möglich
- Constructor Promotion verwenden
- Rückgabetypen immer deklarieren

## Beispiel
```php
<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Service;

use TYPO3\CMS\Core\Resource\ResourceFactory;

final class PdfConversionService
{
    public function __construct(
        private readonly ResourceFactory $resourceFactory,
        private readonly string $ghostscriptPath = '/usr/bin/gs',
    ) {}

    public function convert(string $pdfPath): array
    {
        // ...
    }
}
```
```

### 4.3 `security.md`

```markdown
---
paths:
  - "Classes/**/*.php"
---

# Sicherheitsrichtlinien

## PDF-Verarbeitung
- Ghostscript IMMER mit -dSAFER -dBATCH -dNOPAUSE aufrufen
- MIME-Type vor Verarbeitung prüfen: finfo_file() === 'application/pdf'
- Maximale Dateigröße prüfen (konfigurierbar, Default: 50 MB)
- Temporäres Verzeichnis für Konvertierung, danach aufräumen
- proc_open() mit Timeout für Ghostscript-Prozess

## SQL
- IMMER QueryBuilder mit createNamedParameter() verwenden
- Niemals SQL-Strings konkatenieren
- Prepared Statements für alle Queries

## Output
- Fluid Auto-Escaping nicht deaktivieren (kein {value -> f:format.raw()})
- Ausnahme: nur wenn bewusst HTML ausgegeben wird UND die Quelle vertrauenswürdig ist
- htmlspecialchars() für manuellen Output in PHP

## Backend
- TYPO3 FormProtection (CSRF-Token) für alle Backend-Aktionen
- $GLOBALS['BE_USER']->check() für Berechtigungsprüfungen
- Keine sensitive Daten in FlexForm-XML speichern

## Dateisystem
- Alle Dateizugriffe über TYPO3 FAL, nie direkt über Filesystem-Funktionen
- Keine user-supplied Pfade ohne Validierung verwenden
- Upload-Verzeichnis: nur innerhalb von fileadmin/user_upload/tx_digitalpageflip/
```

### 4.4 `frontend.md`

```markdown
---
paths:
  - "Resources/Public/**/*.js"
  - "Resources/Public/**/*.css"
  - "Resources/Private/Templates/**/*.html"
  - "Resources/Private/Partials/**/*.html"
  - "Resources/Private/Layouts/**/*.html"
---

# Frontend-Regeln

## JavaScript
- Vanilla JS (ES Modules), kein jQuery
- StPageFlip als einzige externe Dependency
- const als Default, let nur bei Reassignment, niemals var
- data-Attribute für JS-Konfiguration (data-flipbook-pages, data-flipbook-config)
- Event Delegation wo sinnvoll

## CSS
- Minimales Styling, nur das Nötigste für den Flipbook-Container
- BEM-Namenskonvention: .digital-page-flip, .digital-page-flip__page
- Mobile First: Basis-Styles für kleine Screens, min-width Breakpoints
- Custom Properties für konfigurierbare Werte (--dpf-max-width, --dpf-aspect-ratio)
- Keine CSS-Frameworks, Extension soll neutral bleiben

## Fluid Templates
- Layouts/Default.html als Basis-Layout
- data-Attribute statt Inline-JS in Templates
- ViewHelper für JSON-Datenaufbereitung (FlipbookDataViewHelper)
- Keine Logik in Templates — nur Darstellung

## Accessibility
- Flipbook-Container: role="region", aria-label="Blätterkatalog: {title}"
- Tastaturnavigation: Pfeiltasten für Seitenwechsel
- prefers-reduced-motion: Blätteranimation deaktivieren
- Alternativtext für Seitenbilder (Seitennummer)
```

### 4.5 `testing.md`

```markdown
---
paths:
  - "Tests/**/*.php"
  - "Classes/**/*.php"
---

# Test-Konventionen

## Struktur
- Tests/Unit/ → Unit Tests (keine DB, keine TYPO3-Runtime)
- Tests/Functional/ → Functional Tests (mit TYPO3-Framework)
- Testklassen spiegeln Classes/-Struktur: Tests/Unit/Service/PdfConversionServiceTest.php

## Naming
- Testklasse: {ClassName}Test
- Testmethode: test{MethodName}{Szenario} oder @test Annotation
- Beispiel: testConvertReturnsPageImagesForValidPdf()

## Unit Tests (Priorität)
- PdfConversionService: Validierung, Kommandozusammenstellung, Fehlerbehandlung
- FlipbookDataViewHelper: JSON-Output-Format
- Domain Models: Getter/Setter, Typ-Validierung

## Mocking
- Ghostscript-Aufrufe mocken (nicht tatsächlich ausführen in Unit Tests)
- FAL ResourceFactory mocken
- TYPO3 Umgebung in Functional Tests über Testing Framework

## QA-Pipeline
- Vor jedem Commit ausführen:
  1. php-cs-fixer fix --dry-run --diff
  2. phpstan analyse
  3. rector process --dry-run
  4. phpunit --testsuite Unit
```

### 4.6 `git-workflow.md`

```markdown
# Git Workflow

## Branch-Naming
- feature/{beschreibung} → Neue Features
- fix/{beschreibung} → Bugfixes
- refactor/{beschreibung} → Refactoring

## Commit-Format
- feat: Neues Feature hinzugefügt
- fix: Bugfix
- refactor: Code-Refactoring ohne Funktionsänderung
- test: Tests hinzugefügt/geändert
- docs: Dokumentation
- chore: Build, Dependencies, Konfiguration

## Beispiel
feat: PDF-zu-WebP Konvertierung via Ghostscript implementiert
fix: MIME-Type Validierung für PDFs mit falscher Extension
test: Unit Tests für PdfConversionService hinzugefügt

## Regeln
- Jeder Commit muss QA-Pipeline bestehen
- Kein Force-Push auf main/develop
- Feature-Branches: kurz halten, regelmäßig rebasen
```

---

## 5. Zusammenspiel der Dateien

```
Session-Start
    │
    ▼
CLAUDE.md geladen (< 200 Zeilen)
    │
    ├── Projekt-Kontext ✓
    ├── Tech-Stack ✓
    ├── Build-Kommandos ✓
    ├── Architektur-Überblick ✓
    └── Sicherheits-Grundregeln ✓
    │
    ▼
Arbeit an Classes/Service/PdfConversionService.php
    │
    ├── .claude/rules/typo3-conventions.md  ← paths: Classes/**/*.php
    ├── .claude/rules/php-code-style.md     ← paths: Classes/**/*.php
    ├── .claude/rules/security.md           ← paths: Classes/**/*.php
    └── .claude/rules/testing.md            ← paths: Classes/**/*.php
    │
    ▼
Claude kennt: Namespace, Code-Style, Sicherheitsregeln, Test-Erwartungen
    │
    ▼
Konsistenter, sicherer, testbarer Code
```

---

## 6. Wartung & Pflege

| Aufgabe | Frequenz | Verantwortlich |
|---|---|---|
| CLAUDE.md aktualisieren bei Architekturänderungen | Bei Bedarf | Entwickler |
| Rules reviewen auf Konsistenz | Monatlich | Entwickler |
| Neue Rules bei neuen Patterns erstellen | Bei Bedarf | Entwickler |
| v14-Migration: Rules anpassen | Beim Relaunch | Entwickler |

---

## 7. Offene Punkte

- [ ] CLAUDE.md finalisieren und ins Projekt-Repository committen
- [ ] Rule-Dateien erstellen und testen
- [ ] Prüfen ob zusätzliche Rules für v14-Migration sinnvoll sind
- [ ] Optional: CI/CD Rule für GitHub Actions / GitLab CI
