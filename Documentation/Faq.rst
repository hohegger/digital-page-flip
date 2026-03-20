.. _faq:

===
FAQ
===

The PDF conversion fails
=========================

Verify that Ghostscript and ImageMagick are installed and accessible
by the web server user. Check the TYPO3 log for detailed error messages.
You can also trigger conversion via CLI to see direct output:

.. code-block:: bash

    vendor/bin/typo3 digitalpageflip:convert <uid>

The flipbook is cut off on iOS Safari
======================================

Ensure the embedding site includes ``viewport-fit=cover`` in the
viewport meta tag. See :ref:`Configuration <configuration>` for details.

The flipbook does not resize when rotating the device
=====================================================

The extension uses ``visualViewport.resize`` and a ``ResizeObserver``
to handle dynamic viewport changes. If you experience issues, ensure
no parent container has a fixed height that prevents the flipbook
from resizing.

How do I update the flipbook after a PDF change?
=================================================

Upload the new PDF and set the conversion status back to "Pending",
then save. The old page images are replaced automatically.
