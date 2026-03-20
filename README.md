# Digital Page Flip

[![TYPO3 12](https://img.shields.io/badge/TYPO3-12-orange.svg)](https://get.typo3.org/version/12)
[![TYPO3 13](https://img.shields.io/badge/TYPO3-13-orange.svg)](https://get.typo3.org/version/13)
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

> **Private repository:** A GitHub token with `repo` scope is required.
> See the [Installation documentation](Documentation/Installation.rst) for details.

## Usage

1. Create a **Flipbook** record in the TYPO3 backend and upload a PDF
2. The PDF is automatically converted to browsable page images
3. Place the **Digital Page Flip** content element on any page
4. The interactive flipbook is rendered in the frontend

See the [full documentation](Documentation/Index.rst) for detailed instructions.

## Requirements

- TYPO3 12.4 - 13.4
- PHP >= 8.2
- Ghostscript >= 10.0
- ImageMagick >= 6.9
- Node.js >= 22 (development only)

## License

GPL-2.0-or-later
