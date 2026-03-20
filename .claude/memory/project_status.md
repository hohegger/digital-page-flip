---
name: Project status digital_page_flip
description: Current implementation status of the TYPO3 flipbook extension — what is done, what is open
type: project
---

## Implementierungsstatus (Stand: 2026-03-19)

### Phase 1: Backend + PDF-Konvertierung — FERTIG
- Domain Models (Flipbook, Page), TCA, FlexForm, FAL
- PdfConversionService: Ghostscript 300 DPI → PNG → WebP (Quality 90)
- Automatische Konvertierung beim Speichern (DataHandler Hook)
- CLI-Command `digitalpageflip:convert` als Alternative
- Seiten-Normalisierung (alle Pages auf gleiche Dimensionen)

### Phase 2: Frontend + Flipbook-Rendering — FERTIG
- Extbase Plugin (FlipbookController, list + show Action)
- StPageFlip mit DOM-basiertem Rendering (loadFromHTML, nicht Canvas)
  → Schärfere Bildqualität durch native Browser-Bildskalierung
- Doppelseiten-Modus, Cover rechts, alle Seiten soft (weich)
- Navigation: Pfeile links/rechts neben dem Buch, Seitenzähler unten
- Tastatur (Pfeiltasten), Touch/Swipe auf Mobil
- Vite Build-Pipeline (TypeScript + CSS)
- Viewer füllt Viewport (kein Scrollen)

### Phase 3: Testing & QA — IN ARBEIT
- Unit Tests implementiert (2026-03-20): FlipbookTest, PageTest, PdfConversionServiceTest, FlipbookDataViewHelperTest, ConvertPdfCommandTest, FlipbookCleanupServiceTest, CleanupCommandTest
- dg/bypass-finals als Dev-Dependency für Mocking finaler Klassen
- QA-Pipeline (`composer ci:all`) läuft grün (69 Tests, 107 Assertions)
- Functional Tests: nur Stubs vorhanden
- Kein formales Sicherheitsaudit

### Phase 4: TYPO3 v13 Readiness (2026-03-20)
- Version-Constraints auf ^12.4 || ^13.4 erweitert (ext_emconf + composer.json)
- Controller: setTemplate() durch ForwardResponse ersetzt (v13/v14-sicher)
- GeneralUtility::makeInstance() durch Container/new ersetzt wo möglich
- @todo markiert: DataHandler SC_OPTIONS Hooks → PSR-14 Events (erst in v13 möglich, keine v12-Alternative)

### Phase 5: TYPO3 v14 Migration — OFFEN (nach Relaunch)

### Bekannte offene Punkte
- Build-Output (Resources/Public/Build/) ist gitignored — muss auf dem Server via `npm run build` oder CI erzeugt werden
- DDEV additional.php liegt auch unter .ddev/ als Template — wird beim ersten `ddev typo3-setup` kopiert

### Erledigte Punkte (2026-03-20)
- fluid_styled_content aus require entfernt (war nicht verwendet)
- FAL Garbage Collection implementiert: FlipbookCleanupService, CleanupCommand, Re-Konvertierung räumt auf, Löschung räumt auf

**Why:** Dokumentation für Session-Kontinuität — neue Gespräche können hier anknüpfen.

**How to apply:** Bei Fortsetzung diesen Status lesen und mit Phase 3 (Testing) oder offenen Punkten weitermachen.
