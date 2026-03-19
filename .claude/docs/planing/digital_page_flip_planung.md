# Digital Page Flip — Planungsdokument

**TYPO3-Extension: `digital_page_flip`**
**Stand:** 18. März 2026

---

## 1. Projektziel

Entwicklung einer TYPO3-Extension, die es ermöglicht, PDF-Handzettel eines Bio-Supermarkts in einen blätterbaren Online-Katalog umzuwandeln. Die PDF wird über das TYPO3-Backend hochgeladen und im Frontend als interaktiver Flipbook-Katalog dargestellt.

---

## 2. Nomenklatur

| Aspekt             | Wert                                      |
| ------------------- | ----------------------------------------- |
| Vendor              | Kit                                       |
| Extension Key       | `digital_page_flip`                       |
| PHP-Namespace       | `\Kit\DigitalPageFlip`                    |
| Composer-Paket      | `kit/digital-page-flip`                   |
| Tabellenpräfix      | `tx_digitalpageflip_*`                    |
| FAL-Speicher        | `fileadmin/user_upload/tx_digitalpageflip/` |

---

## 3. Technische Architektur

### 3.1 PDF-Verarbeitung

- **Methode:** Serverseitige Konvertierung der PDF in Einzelbilder (pro Seite)
- **Tool:** Ghostscript (auf dem Server verfügbar, Version 10.0.0)
- **Bildformat:** WebP als Primärformat mit PNG-Fallback
- **Ablauf:** PDF-Upload → Ghostscript extrahiert Seiten → Bilder werden im FAL-System abgelegt

### 3.2 Flipbook-Bibliothek

- **Bibliothek:** StPageFlip (vanilla JS, keine Abhängigkeiten)
- **Repository:** https://github.com/Nodlik/StPageFlip
- **Rendering:** Canvas-Modus für serverseitig generierte Seitenbilder
- **Features:** Realistischer Seitenumblätter-Effekt, Drag & Swipe, konfigurierbare Swipe-Distanz
- **Begründung:** Kein jQuery nötig, aktiv gepflegt, zukunftssicher für TYPO3 v14

### 3.3 Upload & Verwaltung

- **Upload:** Ausschließlich über das TYPO3-Backend (Redakteure)
- **Kein Frontend-Upload** — reduziert die Angriffsfläche erheblich
- **Speicherort:** FAL-System unter `fileadmin/user_upload/tx_digitalpageflip/`

### 3.4 Frontend-Features

- Responsive Design (Mobile-First)
- Touch/Swipe-Gesten auf mobilen Geräten
- Realistischer Blättereffekt (Seite "anpacken" und umblättern)
- Neutrales, minimalistisches Template (nur der Katalog, keine zusätzliche UI)
- Kein Vollbild, Zoom oder Seitenübersicht in der ersten Version

---

## 4. TYPO3-Kompatibilität

### 4.1 Zielversionen

- **Primärentwicklung:** TYPO3 v12.4 LTS
- **Zielmigration:** TYPO3 v14 (geplanter Relaunch)

### 4.2 Architekturprinzipien (v12 → v14 ready)

Diese Punkte sind als **wichtige Abhängigkeit** verankert:

- Extbase + Fluid als Framework-Basis
- PSR-14 Events statt Legacy-Hooks
- Dependency Injection (DI) durchgängig
- Keine deprecated APIs verwenden
- Migration auf v14 soll im Wesentlichen nur ein Composer-Constraint-Update + minimale Anpassungen erfordern

---

## 5. Sicherheitsanforderungen

- Kein Frontend-Upload (nur authentifizierte Backend-User)
- MIME-Type-Validierung der hochgeladenen PDF
- Ghostscript in Sandbox-Modus (`-dSAFER`) für die PDF-Verarbeitung
- Keine direkte Ausführung von PDF-Inhalten
- TYPO3 CSRF-Schutz für alle Backend-Aktionen
- Generierte Bilder werden im FAL-System verwaltet (kein direkter Dateizugriff)

---

## 6. Server-Infrastruktur

| Aspekt         | Status                          |
| -------------- | ------------------------------- |
| Server         | Hetzner Managed Server          |
| Ghostscript    | ✅ Installiert (Version 10.0.0) |
| ImageMagick    | ✅ Installiert (Version 6.9.11-60, inkl. WebP-Delegate) |
| PHP-Version    | PHP 8.2 (empfohlen — kompatibel mit v12.4 und v14) |
| TYPO3-Version  | v12.4 LTS                       |

---

## 7. Offene Punkte / Nächste Schritte

- [x] ImageMagick-Verfügbarkeit auf dem Server prüfen — ✅ vorhanden inkl. WebP
- [x] PHP-Version klären — PHP 8.2 empfohlen (v12 + v14 kompatibel)
- [x] Detaillierte Extension-Architektur erstellen — ✅ siehe `digital_page_flip_architektur.md`
- [x] KI-Integration planen (CLAUDE.md + .claude/rules/) — ✅ siehe `digital_page_flip_claude_integration.md`
- [x] StPageFlip Asset-Pipeline planen — ✅ npm + Vite, siehe Architektur Abschnitt 4
- [ ] Erste Entwicklungsphase: Backend-Modul + PDF-Konvertierung
- [ ] Zweite Entwicklungsphase: Frontend-Plugin + Flipbook-Rendering
- [ ] Testing & Sicherheitsaudit
- [ ] Migration auf TYPO3 v14 nach Relaunch
