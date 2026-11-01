# Digital Page Flip

[![TYPO3 14](https://img.shields.io/badge/TYPO3-14-orange.svg)](https://get.typo3.org/version/14)
[![Latest Stable Version](https://img.shields.io/github/v/tag/hohegger/digital-page-flip?label=version)](https://github.com/hohegger/digital-page-flip)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)

A TYPO3 extension that converts PDF flyers into browsable online flipbook
catalogs with realistic page-turning effects.

## Features

- 📄 Automatic PDF-to-WebP conversion via Ghostscript + ImageMagick
- 📖 Realistic page-flip animation using the page-flip library
- 📱 Responsive design with single-page mode on mobile devices
- ⌨️ Keyboard and touch/swipe navigation
- 🔗 Configurable sidebar with PDF download, privacy and imprint links
- ⚡ Vite-built frontend assets with cache-busting hashes
- 🖼️ Lazy loading for optimized performance

## Installation

### Composer

```bash
composer require kit/digital-page-flip
```

Then activate the extension:

```bash
vendor/bin/typo3 extension:setup
vendor/bin/typo3 cache:flush
```

### Activate the site set (required)

Since 2.0.0 the extension ships its TypoScript as a **site set** instead of a
static TypoScript template. Add it to your site configuration — either in the
**Sites** module (*Sets for this Site*) or in
`config/sites/<identifier>/config.yaml`:

```yaml
dependencies:
  - kit/digital-page-flip
```

Without this step the flipbook content element renders without templates and
settings.

> **Private repository:** A GitHub token with `repo` scope is required.
> See the [Installation documentation](Documentation/Installation.rst) for details.

## Usage

1. Create a **Flipbook** record in the TYPO3 backend and upload a PDF
2. The PDF is automatically converted to browsable page images
3. Place the **Digital Page Flip** content element on any page
4. The interactive flipbook is rendered in the frontend

### Validity and automatic mode

Flipbooks have a validity period: `valid_from`, `valid_until` (empty = from + 13
days), `lead_days` (default `2`, days online before `valid_from`) and a
`week_label` (generated as e.g. `12_13`; a manual value wins). Setting
`valid_from` derives the online start (00:00 of `valid_from` minus `lead_days`).

The **current** flipbook is the one with the latest online start not in the
future among visible, fully converted records with `valid_from` set in the
configured `storagePid` (0 = all). In the content element, set **Anzeige** to
*Aktuellen Handzettel automatisch anzeigen* to always show it. Nothing is
rendered if there is none.

Page caches are tagged and expire at the next online start, so the switch needs
no manual cache flush.

### ViewHelper

```html
<html xmlns:dpf="http://typo3.org/ns/Kit/DigitalPageFlip/ViewHelpers" data-namespace-typo3-fluid="true">
<f:variable name="handout" value="{dpf:currentFlipbook()}" />
<f:if condition="{handout}">{handout.title} ({handout.weekLabel})</f:if>
```

Arguments: `upcoming` (bool, next scheduled flipbook) and `pageUid` (site lookup
without frontend request, e.g. backend preview). Returns `null` if none.

See the [full documentation](Documentation/Index.rst) for detailed instructions.

## Requirements

- TYPO3 14.3 (with `typo3/cms-fluid-styled-content` — the site set loads it
  automatically as a dependency)
- PHP >= 8.2
- Ghostscript >= 10.0
- ImageMagick >= 6.9
- Node.js >= 22 (development only)

> **Version compatibility:** For TYPO3 v12/v13 use the 1.x line (`^1`);
> the 2.x line is TYPO3 v14-only.

## License

GPL-2.0-or-later
