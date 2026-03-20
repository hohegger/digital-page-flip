.. _usage:

=====
Usage
=====

Creating a flipbook
===================

1. In the TYPO3 backend, switch to the **List module**.
2. Select a page in the page tree.
3. Click **Create new record** and choose **Flipbook**.
4. Enter a **title** (e.g. "Weekly Flyer KW 12").
5. Upload a PDF file in the **PDF file** field (or select one from FAL).
6. **Save** — the conversion starts automatically.
   A green flash message confirms:
   *"PDF was successfully converted. X pages generated."*
7. The **conversion status** changes to "Completed" and the generated
   pages are visible in the Pages tab.

If conversion fails (red flash message), set the status back to
"Pending" and save again. Alternatively, trigger conversion via CLI:

.. code-block:: bash

    # Convert a single flipbook
    vendor/bin/typo3 digitalpageflip:convert <uid>

    # Convert all pending flipbooks
    vendor/bin/typo3 digitalpageflip:convert

Placing the content element
============================

1. Switch to the **Page module** and navigate to the desired page.
2. Create a **new content element**.
3. In the **Plugins** tab, select **Digital Page Flip**.
4. Choose the desired flipbook in the **Flipbook** field.
5. **Save** — the flipbook is displayed in the frontend.

If no flipbook is selected, the plugin shows an overview of all
published catalogs.

Frontend interactions
=====================

**Navigation:**
    Previous/next buttons and a page indicator below the flipbook.

**Keyboard:**
    Arrow keys left/right to turn pages.

**Touch:**
    Swipe gestures on mobile devices.

**Sidebar:**
    Vertical toolbar (desktop) or horizontal topbar (tablet/mobile)
    with: jump to first page, PDF download, home page, privacy policy
    and imprint links (configurable via TypoScript constants).

**Mobile:**
    Single-page portrait mode on screens smaller than 768px.

**Responsive:**
    The flipbook dynamically adapts to viewport and container size
    changes, including iOS Safari toolbar show/hide.
