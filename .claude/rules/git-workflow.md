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

## Remote
- origin → GitHub (git@github.com:hohegger/digital-page-flip.git) — privat

## Release / Versionierung
- Getaggte Releases für Composer-Distribution (GitHub, --prefer-dist)
- .gitattributes export-ignore sorgt dafür, dass Development-Dateien nicht ausgeliefert werden
- Workflow:
  1. Version in ext_emconf.php aktualisieren
  2. Tag erstellen: git tag -a v1.x.0 -m "v1.x.0 — Beschreibung"
  3. Pushen: git push origin main --tags
  4. Im TYPO3-Projekt: composer update kit/digital-page-flip

## Regeln
- Jeder Commit muss QA-Pipeline bestehen
- Kein Force-Push auf main/develop
- Feature-Branches: kurz halten, regelmäßig rebasen
