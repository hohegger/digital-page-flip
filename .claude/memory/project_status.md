---
name: Project status digital_page_flip
description: Current implementation status of the TYPO3 flipbook extension — what is done, what is open
type: project
---

## Implementierungsstatus (Stand: 2026-03-20)

### Phase 1: Backend + PDF-Konvertierung — FERTIG
- Domain Models (Flipbook, Page), TCA, FAL
- PdfConversionService: Ghostscript 300 DPI → PNG → WebP (Quality 90)
- Automatische Konvertierung beim Speichern (DataHandler Hook)
- CLI-Command `digitalpageflip:convert` als Alternative
- Seiten-Normalisierung (alle Pages auf gleiche Dimensionen)

### Phase 2: Frontend + Flipbook-Rendering — FERTIG
- Eigener CType `digitalpageflip_flipbook` (kein generisches Plugin-CE)
- Flipbook-Auswahl als echtes TCA-Feld auf tt_content (kein FlexForm)
- Content Element Wizard Registrierung via PageTSconfig
- StPageFlip mit DOM-basiertem Rendering (loadFromHTML, nicht Canvas)
- Doppelseiten-Modus, Cover rechts, alle Seiten soft (weich)
- Navigation: Pfeile links/rechts neben dem Buch, Seitenzähler unten
- Tastatur (Pfeiltasten), Touch/Swipe auf Mobil
- Vite Build-Pipeline (TypeScript + CSS)
- Viewer füllt Viewport (kein Scrollen)

### Phase 3: Testing & QA — FERTIG
- 69 Unit Tests, 107 Assertions — Pipeline grün
- Getestete Klassen: FlipbookTest, PageTest, PdfConversionServiceTest, FlipbookDataViewHelperTest, ConvertPdfCommandTest, FlipbookCleanupServiceTest, CleanupCommandTest
- dg/bypass-finals für Mocking finaler Klassen
- QA-Pipeline (`composer ci:all`): CS-Fixer, PHPStan Level 8, Rector, PHPUnit — alles grün
- Functional Tests: nur Stubs (bewusst zurückgestellt)

### Phase 4: FAL Garbage Collection — FERTIG
- FlipbookCleanupService: zentraler Cleanup für alle Lifecycle-Szenarien
- Re-Konvertierung räumt alte Pages/Dateien/Referenzen auf
- Flipbook-Löschung räumt FAL-Dateien, Ordner und Referenzen auf (processCmdmap Hook)
- CLI-Command `digitalpageflip:cleanup` mit --dry-run für verwaiste Daten
- fluid_styled_content aus require entfernt (war nicht verwendet)

### Phase 5: TYPO3 v13 Readiness — FERTIG (soweit in v12 möglich)
- Version-Constraints auf ^12.4 || ^13.4 erweitert (ext_emconf + composer.json)
- Controller: setTemplate() durch ForwardResponse ersetzt (v13/v14-sicher)
- GeneralUtility::makeInstance() durch Container/new ersetzt wo möglich
- @todo markiert: DataHandler SC_OPTIONS Hooks → PSR-14 Events (erst in v13 möglich, keine v12-Alternative)

### Phase 6: TYPO3 v14 Migration — OFFEN (nach Relaunch)

### Bekannte offene Punkte
- Build-Output (Resources/Public/Build/) ist gitignored — muss auf dem Server via `npm run build` oder CI erzeugt werden
- DDEV additional.php liegt auch unter .ddev/ als Template

**Why:** Dokumentation für Session-Kontinuität — neue Gespräche können hier anknüpfen.

**How to apply:** Bei Fortsetzung diesen Status lesen und mit offenen Punkten oder neuen Features weitermachen.
