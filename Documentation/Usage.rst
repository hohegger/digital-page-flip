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
6. **Save** — the conversion is scheduled automatically.
   A blue info message confirms:
   *"Die PDF-Konvertierung wurde eingeplant und startet in Kürze automatisch."*
7. The TYPO3 **Scheduler** picks up the task within 1-2 minutes.
   The **conversion status** (visible in the Pages tab) changes from
   "Pending" to "Processing" and finally to "Completed".

If conversion fails, the status changes to "Error". The Scheduler
retries automatically on the next run. You can also trigger conversion
manually via CLI:

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

Scheduler setup
================

The PDF conversion runs asynchronously via the TYPO3 Scheduler.
After installation, set up the Scheduler task:

1. Go to **System > Scheduler** in the TYPO3 backend.
2. Create a new task: **Execute console commands** →
   ``digitalpageflip:convert``.
3. Set type to **Recurring** with an interval of **120 seconds**
   (2 minutes).
4. Activate and save the task.

Make sure the TYPO3 Scheduler is triggered by a system cron job
(e.g. every minute via ``vendor/bin/typo3 scheduler:run``).
