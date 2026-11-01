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

Alternatively set **Anzeige** to *Aktuellen Handzettel automatisch anzeigen*
(see below). Without a flipbook to show, the element renders the short notice
"Derzeit ist kein Katalog verfügbar." plus links to the imprint and privacy
pages (only those configured in the site settings ``sidebar.*``).

Catalog page without the website layout
=======================================

The site set ships a backend layout **Flipbook (leere Seite)**
(identifier ``DigitalPageFlipBlank``, one column ``main`` with colPos 0) and a
matching page object ``lib.digitalPageFlip.blankPage`` (``PAGE`` with
``PAGEVIEW``). It outputs only the HTML document (title, viewport meta tag)
and the content of the ``main`` column - no header, footer, CSS or JavaScript
of the website; the flipbook brings its own assets.

Because the site set of the extension is loaded before the site package,
the site package has to switch the page object itself, at the **end** of its
TypoScript setup (after its own ``page`` definition):

.. code-block:: typoscript

    [tree.pagelayout == "pagets__DigitalPageFlipBlank"]
    page >
    page < lib.digitalPageFlip.blankPage
    [END]

Then select the backend layout *Flipbook (leere Seite)* in the page properties
of the catalog page and place the content element in its column.

Validity and automatic publishing
=================================

Each flipbook has a validity period (palette *Validity* in the record):

``valid_from`` (Gültig von)
    First day of the flyer's validity. Setting it switches the record to
    automatic scheduling: the **online start** is derived as 00:00 of the day
    ``valid_from`` minus ``lead_days`` (stored in the record's ``starttime``).
    Clearing the field makes the record manually controllable again.

``valid_until`` (Gültig bis)
    Last day of validity. If empty, ``valid_from`` plus 13 days is assumed.
    It is only used for the week label; it never takes a flipbook offline.

``lead_days`` (Vorlauf)
    Days the flipbook goes online before ``valid_from``. Default: ``2``.

``week_label`` (Kalenderwoche)
    Calendar weeks as ``<ISO week of valid_from>_<ISO week of valid_until - 6 days>``,
    two digits each (e.g. ``12_13``); a single number if the second week is not
    greater than the first. Generated automatically; a value entered by hand wins.

The **current** flipbook is the one with the latest online start that is
not in the future, among all records that are not hidden or deleted, have
``valid_from`` set, are fully converted (status *Completed*) and lie in the
configured ``storagePid`` (``0`` = all pages). On a tie the highest uid wins.
A successor that is not yet converted at its online start does not replace
its predecessor until the conversion has finished.

Showing the current flipbook automatically
==========================================

In the content element, the field **Anzeige** offers two modes:

* *Ausgewählter Handzettel* - shows the flipbook chosen in the **Flipbook** field.
* *Aktuellen Handzettel automatisch anzeigen* - always shows the current flipbook
  (see above); the **Flipbook** field is hidden. If there is no current flipbook,
  the neutral notice described above is shown instead of the viewer.

Using the current flipbook in your own templates
================================================

The ``currentFlipbook`` ViewHelper returns the current flipbook (or ``null``)
for use in any Fluid template, e.g. from another extension or content block:

.. code-block:: html

    <html
        xmlns:dpf="http://typo3.org/ns/Kit/DigitalPageFlip/ViewHelpers"
        data-namespace-typo3-fluid="true"
    >
    <f:variable name="handout" value="{dpf:currentFlipbook()}" />
    <f:if condition="{handout}">
        {handout.title} ({handout.weekLabel}), {handout.validFrom -> f:format.date(format: 'd.m.Y')}
    </f:if>

Arguments: ``upcoming`` (bool, default ``false``) returns the next scheduled
flipbook instead; ``pageUid`` (int) determines the site from a page when there
is no frontend request (e.g. backend preview). The returned object offers
``uid``, ``pid``, ``title``, ``validFrom``, ``validUntil``, ``onlineStart``,
``weekLabel``, ``pdf``, ``cover`` and ``converted``.

Cache behavior
==============

Pages using the auto mode or the ViewHelper are tagged with the flipbook table
cache tag, so saving or deleting a flipbook flushes them. Their cache lifetime
is additionally capped at the next online start, so the switch to a new flipbook
happens on time without a manual cache flush.

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
