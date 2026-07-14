# Changelog

Alle relevanten Änderungen an dieser Extension werden in dieser Datei dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.1.0/)
und dieses Projekt folgt [Semantic Versioning](https://semver.org/lang/de/).

## [2.0.0] - 2026-07-14

### Changed

- **BREAKING:** TYPO3 v14-only release (`^14.3`). For TYPO3 v12/v13 use the 1.x line (`^1`).
- Migrated `FlipbookDataViewHelper` from `renderStatic()` to `render()` (Fluid 5).
- Extbase model uses `Attribute\ORM` namespace with named `Cascade` argument.
- `PdfConversionService` uses the v14 `Enum\DuplicationBehavior` (class removed in v14).
- Rector config raised to the TYPO3 v14 level set.

### Removed

- TCA `ctrl.searchFields` option (removed in TYPO3 v14, #106972) — replaced by per-field `searchable`.

## [1.2.0] - 2026-04-10

### Changed

- PDF-Konvertierung laeuft jetzt asynchron per TYPO3 Scheduler statt synchron beim Speichern
- DataHandlerHook setzt nur noch Status PENDING und zeigt Info-Flash-Message
- ConvertPdfCommand ist als Scheduler-Task verfuegbar (`schedulable: true`)

### Fixed

- Ghostscript Exit-Code wurde falsch gelesen — `proc_close()` liefert -1 wenn `proc_get_status()` den Code bereits konsumiert hat

## [1.1.0] - 2026-03-20

### Security

- SQL Injection Pattern in `FlipbookCleanupService::hardDeleteByField()` behoben — Raw SQL String durch `createNamedParameter()` ersetzt
- PDF-Dateigroessen-Validierung vor Konvertierung (konfigurierbar, Default: 50 MB)
- Ghostscript-Prozess nutzt jetzt `proc_open()` mit Timeout (120s) statt `exec()`

### Fixed

- FlipbookController prueft Conversion-Status — zeigt Hinweismeldung statt leerem Viewer bei nicht-fertigen Flipbooks
- DataHandlerHook erlaubt Re-Konvertierung wenn PDF bei bereits konvertiertem Flipbook ersetzt wird
- ImageMagick Exit-Code wird in `normalizePageDimensions()` geprueft und bei Fehler geloggt
- Nicht existierende `publishDate`-Tests aus FlipbookTest entfernt

### Added

- Unit Tests fuer `validateFileSize()` (8 Tests, Boundary Conditions)
- Unit Tests fuer DataHandlerHook Re-Konvertierungslogik
- Unit Tests fuer FlipbookController Status-Pruefung

## [1.0.0] - 2026-03-15

### Added

- Initiales Release der Extension
- PDF-zu-WebP/PNG Konvertierung via Ghostscript + ImageMagick
- Blaetterbarer Flipbook-Viewer mit page-flip Library
- Sidebar-Navigation mit Seitenvorschau
- Responsive Viewer mit iOS Safari Support
- FAL-basierte Dateiverwaltung
- CLI-Command fuer Batch-Konvertierung und Garbage Collection
- Vite + TypeScript + SCSS Build-Pipeline
