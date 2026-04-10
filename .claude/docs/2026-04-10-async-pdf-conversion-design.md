# Asynchrone PDF-Konvertierung

## Problem

Die PDF-zu-Bild-Konvertierung (Ghostscript + ImageMagick) läuft synchron im
DataHandler-Hook beim Speichern eines Flipbook-Records. Das blockiert den
Backend-Speicherprozess, der Redakteur kann nicht weiterarbeiten und es besteht
Timeout-Gefahr bei großen PDFs.

## Lösung

Den DataHandler-Hook entschlacken: Er setzt beim Speichern nur noch den Status
auf PENDING. Die eigentliche Konvertierung übernimmt der bestehende Console
Command `digitalpageflip:convert`, der per TYPO3 Scheduler alle 1-2 Minuten
automatisch läuft.

## Architektur

### Neuer Ablauf

```
Redakteur speichert Flipbook
  └─ DataHandlerHook
       ├─ PDF geändert? → Status = PENDING, Flash-Message: "Konvertierung eingeplant"
       ├─ PDF unverändert, Status COMPLETED? → nichts tun
       └─ Kein PDF? → nichts tun

Scheduler (alle 1-2 Minuten)
  └─ digitalpageflip:convert
       ├─ FlipbookRepository->findPending()  (PENDING + ERROR)
       ├─ Für jeden Flipbook:
       │    ├─ PdfConversionService->convert()
       │    ├─ Erfolg → STATUS_COMPLETED
       │    └─ Fehler → STATUS_ERROR + Logging
       └─ Command beendet
```

### Parallelitätsschutz

- `PdfConversionService->convert()` setzt sofort STATUS_PROCESSING beim Start
- `shouldConvert()` überspringt Records mit STATUS_PROCESSING
- Ein zweiter Scheduler-Lauf kann keinen Konflikt verursachen

### Fehler-Retry

- Bei Fehler setzt der Service STATUS_ERROR
- Beim nächsten Scheduler-Lauf wird automatisch ein Retry versucht
- Kein manuelles Eingreifen nötig (es sei denn, die PDF ist grundsätzlich kaputt)

## Änderungen

### Bestehende Dateien ändern

| Datei | Änderung |
|-------|----------|
| `Classes/Hook/DataHandlerHook.php` | Synchronen `convert()`-Aufruf entfernen, nur noch Status auf PENDING setzen + Info-Flash-Message |
| `Configuration/Services.yaml` | `schedulable: true` zum ConvertPdfCommand hinzufügen |

### Bestehende Dateien prüfen, ggf. anpassen

| Datei | Prüfung |
|-------|---------|
| `Classes/Command/ConvertPdfCommand.php` | Return-Codes sauber setzen, Logging prüfen |

### Keine neuen Klassen nötig

Der bestehende Command und Service decken alles ab.

### Unverändert

- `PdfConversionService` — Kernlogik bleibt identisch
- `Flipbook`-Model — Status-Konstanten passen bereits
- `FlipbookRepository::findPending()` — wird vom Command schon genutzt
- `FlipbookCleanupService` — nicht betroffen
- TCA, ext_tables.sql, Frontend — alles unverändert

## Redakteurs-Erlebnis

- **Nach dem Speichern:** Info-Message (blau): "Die PDF-Konvertierung wurde eingeplant und startet in Kürze automatisch."
- **Status-Anzeige:** Das bestehende readOnly-Feld `conversion_status` zeigt jederzeit den aktuellen Stand
- **Kein Polling, keine Live-Updates** — Redakteur prüft Status durch erneutes Öffnen des Records

## Setup nach Deployment

Im TYPO3 Scheduler-Modul den Command `digitalpageflip:convert` als Task anlegen
mit einem Intervall von 2 Minuten.

## Serverumgebung

- Managed Hetzner Server, Cron-Jobs per KonsoleH
- Kein Root-Zugriff, keine Message Queues (Redis/RabbitMQ)
- TYPO3 Scheduler wird per Cron getriggert
