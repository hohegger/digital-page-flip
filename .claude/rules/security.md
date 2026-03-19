---
paths:
  - "Classes/**/*.php"
---

# Sicherheitsrichtlinien

## PDF-Verarbeitung
- Ghostscript IMMER mit -dSAFER -dBATCH -dNOPAUSE aufrufen
- MIME-Type vor Verarbeitung prüfen: finfo_file() === 'application/pdf'
- Maximale Dateigröße prüfen (konfigurierbar, Default: 50 MB)
- Temporäres Verzeichnis für Konvertierung, danach aufräumen
- proc_open() mit Timeout für Ghostscript-Prozess

## SQL
- IMMER QueryBuilder mit createNamedParameter() verwenden
- Niemals SQL-Strings konkatenieren
- Prepared Statements für alle Queries

## Output
- Fluid Auto-Escaping nicht deaktivieren (kein {value -> f:format.raw()})
- Ausnahme: nur wenn bewusst HTML ausgegeben wird UND die Quelle vertrauenswürdig ist
- htmlspecialchars() für manuellen Output in PHP

## Backend
- TYPO3 FormProtection (CSRF-Token) für alle Backend-Aktionen
- $GLOBALS['BE_USER']->check() für Berechtigungsprüfungen
- Keine sensitive Daten in FlexForm-XML speichern

## Dateisystem
- Alle Dateizugriffe über TYPO3 FAL, nie direkt über Filesystem-Funktionen
- Keine user-supplied Pfade ohne Validierung verwenden
- Upload-Verzeichnis: nur innerhalb von fileadmin/user_upload/tx_digitalpageflip/
