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
