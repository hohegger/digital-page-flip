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
