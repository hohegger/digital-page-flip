.. _configuration:

=============
Configuration
=============

The extension works out of the box with sensible defaults. All settings
can be adjusted via **TypoScript constants** (editable in the TYPO3
Constant Editor).

Flipbook settings
=================

.. list-table::
    :header-rows: 1
    :widths: 30 15 55

    * - Constant
      - Default
      - Description
    * - ``flipbook.width``
      - ``550``
      - Page width in pixels (single page).
    * - ``flipbook.height``
      - ``880``
      - Page height in pixels (single page).
    * - ``flipbook.showCover``
      - ``1``
      - Show first/last page as single cover page.
    * - ``flipbook.swipeDistance``
      - ``30``
      - Swipe sensitivity for touch devices (lower = more sensitive).

The width and height values refer to a **single page**. In double-page
mode the displayed width doubles automatically.

Sidebar links
=============

.. list-table::
    :header-rows: 1
    :widths: 30 15 55

    * - Constant
      - Default
      - Description
    * - ``sidebar.privacyPageUid``
      -
      - Page UID for the privacy policy link.
    * - ``sidebar.imprintPageUid``
      -
      - Page UID for the imprint link.
    * - ``sidebar.homePageUid``
      -
      - Page UID for the home page link.

Sidebar links are only displayed when the respective page UID is set.

Viewport meta tag
=================

For correct rendering on iOS devices with a Home Indicator (iPhone X+),
the embedding TYPO3 site should include ``viewport-fit=cover`` in the
viewport meta tag:

.. code-block:: html

    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

This enables ``env(safe-area-inset-bottom)`` support so the flipbook
does not overlap with the Home Indicator area.
