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
