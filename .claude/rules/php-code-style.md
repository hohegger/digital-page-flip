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
