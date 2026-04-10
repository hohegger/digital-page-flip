# Asynchrone PDF-Konvertierung — Implementierungsplan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** PDF-Konvertierung vom synchronen Speicherprozess entkoppeln — der DataHandler-Hook setzt nur noch PENDING, ein Scheduler-gesteuerter Console Command übernimmt die Konvertierung.

**Architecture:** Der Hook wird entschlackt (kein `convert()`-Aufruf mehr), der bestehende `ConvertPdfCommand` wird als `schedulable` markiert und im TYPO3 Scheduler eingerichtet.

**Tech Stack:** TYPO3 v12 Scheduler, Symfony Console Command, Extbase Persistence

**Spec:** `.claude/docs/2026-04-10-async-pdf-conversion-design.md`

---

### Task 1: DataHandlerHook — synchrone Konvertierung entfernen

**Files:**
- Modify: `Classes/Hook/DataHandlerHook.php`
- Test: `Tests/Unit/Hook/DataHandlerHookTest.php`

**Ziel:** Der Hook soll beim Speichern nur noch den Status auf PENDING setzen und eine Info-Flash-Message anzeigen. Kein `convert()`-Aufruf, kein `PersistenceManager`, kein try/catch um Konvertierung.

- [ ] **Step 1: Test für neues Verhalten schreiben**

In `Tests/Unit/Hook/DataHandlerHookTest.php` — die bestehenden `shouldConvert()`-Tests bleiben unverändert, da sich die Logik nicht ändert. Keine neuen Tests nötig, da `processFlipbook()` private ist und über den Container läuft (nicht unit-testbar). Die `shouldConvert()`-Tests decken die Entscheidungslogik weiterhin ab.

- [ ] **Step 2: Tests ausführen, sicherstellen dass alle grün sind**

Run: `vendor/bin/phpunit --testsuite Unit --filter DataHandlerHookTest`
Expected: Alle 6 Tests PASS (Baseline vor Refactoring)

- [ ] **Step 3: `processFlipbook()` refactoren**

In `Classes/Hook/DataHandlerHook.php` die Methode `processFlipbook()` ersetzen. Der gesamte try/catch-Block mit `conversionService->convert()` wird entfernt. Stattdessen: Status per QueryBuilder auf PENDING setzen + Flash-Message.

Neue `processFlipbook()`-Methode (ersetzt Zeilen 47-112):

```php
private function processFlipbook(int $uid, DataHandler $dataHandler): void
{
    $container = GeneralUtility::getContainer();
    $queryBuilder = $container->get(ConnectionPool::class)
        ->getQueryBuilderForTable(self::TABLE);

    $record = $queryBuilder
        ->select('pdf_file', 'conversion_status', 'page_count')
        ->from(self::TABLE)
        ->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
        )
        ->executeQuery()
        ->fetchAssociative();

    if ($record === false) {
        return;
    }

    $pdfCount = (int) $record['pdf_file'];
    $status = (int) $record['conversion_status'];

    if ($pdfCount === 0) {
        return;
    }

    $pdfChanged = isset($dataHandler->datamap[self::TABLE][$uid]['pdf_file']);
    if (!self::shouldConvert($status, $pdfChanged)) {
        return;
    }

    $connection = $container->get(ConnectionPool::class)
        ->getConnectionForTable(self::TABLE);
    $connection->update(
        self::TABLE,
        ['conversion_status' => Flipbook::STATUS_PENDING],
        ['uid' => $uid],
    );

    $this->addFlashMessage(
        'Die PDF-Konvertierung wurde eingeplant und startet in Kürze automatisch.',
        ContextualFeedbackSeverity::INFO,
    );
}
```

- [ ] **Step 4: Nicht mehr benötigte Imports entfernen**

Folgende use-Statements aus `DataHandlerHook.php` entfernen:

```php
use Kit\DigitalPageFlip\Domain\Repository\FlipbookRepository;
use Kit\DigitalPageFlip\Service\PdfConversionService;
use Throwable;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
```

- [ ] **Step 5: Tests ausführen**

Run: `vendor/bin/phpunit --testsuite Unit --filter DataHandlerHookTest`
Expected: Alle 6 Tests PASS (shouldConvert-Logik unverändert)

- [ ] **Step 6: QA-Pipeline**

Run: `composer ci:php:cs:fix && composer ci:php:stan`
Expected: Keine Fehler

- [ ] **Step 7: Commit**

```bash
git add Classes/Hook/DataHandlerHook.php
git commit -m "refactor: DataHandlerHook auf asynchrone Konvertierung umgestellt

Hook setzt nur noch Status PENDING statt synchron zu konvertieren.
Konvertierung wird per Scheduler-Task übernommen."
```

---

### Task 2: ConvertPdfCommand als schedulable markieren

**Files:**
- Modify: `Configuration/Services.yaml`

**Ziel:** Den bestehenden Command im TYPO3 Scheduler verfügbar machen.

- [ ] **Step 1: `schedulable: true` hinzufügen**

In `Configuration/Services.yaml` den ConvertPdfCommand-Eintrag erweitern:

```yaml
  Kit\DigitalPageFlip\Command\ConvertPdfCommand:
    tags:
      - name: console.command
        command: 'digitalpageflip:convert'
        description: 'Convert PDF files of flipbook records to page images'
        schedulable: true
```

- [ ] **Step 2: YAML-Syntax prüfen**

Run: `php -r "var_export(yaml_parse_file('Configuration/Services.yaml'));"`
Falls `yaml_parse` nicht verfügbar: visuell prüfen, dass die Einrückung korrekt ist (2 Spaces pro Ebene).

- [ ] **Step 3: Commit**

```bash
git add Configuration/Services.yaml
git commit -m "feat: ConvertPdfCommand als Scheduler-Task verfügbar machen

schedulable: true erlaubt die Einrichtung als periodischer
TYPO3 Scheduler Task für die asynchrone PDF-Konvertierung."
```

---

### Task 3: Gesamte QA-Pipeline und Abschluss

**Files:**
- Alle geänderten Dateien

**Ziel:** Sicherstellen, dass die gesamte QA-Pipeline grün ist.

- [ ] **Step 1: PHP-CS-Fixer**

Run: `vendor/bin/php-cs-fixer fix --dry-run --diff`
Expected: Keine Änderungen nötig

- [ ] **Step 2: PHPStan**

Run: `vendor/bin/phpstan analyse`
Expected: Keine Fehler

- [ ] **Step 3: Rector**

Run: `vendor/bin/rector process --dry-run`
Expected: Keine Änderungen vorgeschlagen

- [ ] **Step 4: Unit Tests (komplett)**

Run: `vendor/bin/phpunit --testsuite Unit`
Expected: Alle Tests PASS

- [ ] **Step 5: Ergebnis prüfen**

Run: `git diff main --stat`
Expected: Genau 2 geänderte Dateien:
- `Classes/Hook/DataHandlerHook.php` (refactored)
- `Configuration/Services.yaml` (schedulable hinzugefügt)

---

## Setup nach Deployment (manuell)

Diese Schritte sind nach dem Deployment auf dem Server nötig:

1. **TYPO3 Backend** → Modul "Scheduler" öffnen
2. Neuen Task anlegen: "Execute console commands" → `digitalpageflip:convert`
3. Typ: "Recurring", Intervall: 120 Sekunden (2 Minuten)
4. Task aktivieren und speichern
5. Sicherstellen, dass der TYPO3 Scheduler per Cron läuft (KonsoleH)
