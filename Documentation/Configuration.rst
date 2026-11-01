.. _configuration:

=============
Configuration
=============

The extension works out of the box with sensible defaults. All settings
are defined as **site settings** (:file:`settings.definitions.yaml` of the
site set) and can be edited in the backend via
:guilabel:`Site Management > Settings` for every site that includes the
``kit/digital-page-flip`` set. Alternatively they can be overridden in the
site's own :file:`settings.yaml` or via TypoScript constants
(``plugin.tx_digitalpageflip.*``).

Storage folder and validity
===========================

``plugin.tx_digitalpageflip.persistence.storagePid`` limits the automatic
"current flipbook" selection to one folder (``0`` = all pages). Per flipbook,
the fields ``valid_from``, ``valid_until``, ``lead_days`` (default ``2``) and
``week_label`` (generated automatically) control when it goes online; see
:ref:`usage`.

Flipbook settings
=================

.. list-table::
    :header-rows: 1
    :widths: 30 15 55

    * - Setting
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

    * - Setting
      - Default
      - Description
    * - ``sidebar.privacyPageUid``
      - ``0``
      - Page UID for the privacy policy link.
    * - ``sidebar.imprintPageUid``
      - ``0``
      - Page UID for the imprint link.
    * - ``sidebar.homePageUid``
      - ``0``
      - Page UID for the home page link.

Sidebar links are only displayed when the respective page UID is set
(``0`` hides the link).

Viewport meta tag
=================

For correct rendering on iOS devices with a Home Indicator (iPhone X+),
the embedding TYPO3 site should include ``viewport-fit=cover`` in the
viewport meta tag:

.. code-block:: html

    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

This enables ``env(safe-area-inset-bottom)`` support so the flipbook
does not overlap with the Home Indicator area.
