.. _developer:

=========
Developer
=========

Architecture
============

The extension follows standard Extbase/Fluid conventions:

**PdfConversionService** (``Kit\DigitalPageFlip\Service\PdfConversionService``)
    Core business logic. Converts PDF pages to WebP images using
    Ghostscript (PDF → PNG) and ImageMagick (PNG → WebP). Files are
    stored via FAL in ``fileadmin/user_upload/tx_digitalpageflip/``.

**DataHandlerHook** (``Kit\DigitalPageFlip\Hook\DataHandlerHook``)
    Listens to DataHandler save operations. When a flipbook with a PDF
    is saved, the hook sets ``conversion_status`` to ``PENDING``. The
    actual conversion is handled asynchronously by the Scheduler task.

**ConvertPdfCommand** (``Kit\DigitalPageFlip\Command\ConvertPdfCommand``)
    Symfony Console command (``digitalpageflip:convert``). Finds all
    flipbooks with status ``PENDING`` or ``ERROR`` and runs the
    conversion. Registered as ``schedulable: true`` in Services.yaml,
    so it can be configured as a recurring TYPO3 Scheduler task.

**FlipbookController** (``Kit\DigitalPageFlip\Controller\FlipbookController``)
    Extbase ActionController. Reads the Vite manifest to register
    hashed JS/CSS assets via the TYPO3 AssetCollector.

**Domain Model** (``Kit\DigitalPageFlip\Domain\Model\Flipbook``)
    Extbase entity with relations to Page objects and a FAL file
    reference for the source PDF.

**FlipbookDataViewHelper**
    Provides flipbook configuration (pages, dimensions, UID) as
    data attributes on the container element for the JavaScript
    initialization.

Frontend asset pipeline
=======================

The frontend is built with **Vite 6** (TypeScript + SCSS):

.. code-block:: text

    Resources/Private/
    ├── TypeScript/
    │   └── flipbook-init.ts      # page-flip initialization
    └── Scss/
        ├── flipbook.scss          # Entry point
        ├── _tokens.scss           # Design tokens (CSS custom properties)
        ├── _viewer.scss           # Viewer layout (stage, book, page)
        ├── _sidebar.scss          # Sidebar / topbar
        ├── _controls.scss         # Arrow buttons + pager
        └── _list.scss             # List view

Build output goes to ``Resources/Public/Build/`` with content-hashed
filenames for automatic cache busting. Build artifacts are committed
for distribution.

.. code-block:: bash

    # Build for production
    npm run build

The FlipbookController reads ``.vite/manifest.json`` to resolve the
current hashed filenames and registers them via AssetCollector.

page-flip library
=================

The extension uses `page-flip <https://www.npmjs.com/package/page-flip>`__
(vanilla JS) for the flip animation. Key integration details:

- Pages are rendered as DOM elements (not canvas) for native browser
  image rendering quality.
- A ``ResizeObserver`` dynamically updates ``maxWidth``/``maxHeight``
  on the internal ``setting`` object when the container resizes.
- ``visualViewport.resize`` handles iOS Safari dynamic toolbar changes.
- All pages are forced to ``soft`` density for consistent flip behavior.

Local development (DDEV)
========================

.. code-block:: bash

    # Clone and start
    git clone git@github.com:hohegger/digital-page-flip.git
    cd digital-page-flip
    ddev start

    # Set up TYPO3 (Composer, DB, admin user, extension)
    ddev typo3-setup

    # Build frontend assets
    ddev build

    # Open backend
    ddev launch /typo3

**Login:** ``admin`` / ``Password1!``

Code quality
============

.. code-block:: bash

    # Run all checks
    ddev composer ci:all

    # Individual checks
    ddev composer ci:php:cs       # Code style (PER-CS2.0)
    ddev composer ci:php:cs:fix   # Auto-fix code style
    ddev composer ci:php:stan     # PHPStan (Level 8)
    ddev composer ci:php:rector   # Rector (deprecation check)
    ddev composer ci:php:unit     # Unit tests

Security
========

- Ghostscript runs with ``-dSAFER -dBATCH -dNOPAUSE`` flags.
- PDF MIME type is validated before processing.
- No frontend upload — only authenticated backend users can upload PDFs.
- Fluid auto-escaping for all template output.
- TYPO3 FormProtection (CSRF) for all backend actions.
