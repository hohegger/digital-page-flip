.. _installation:

============
Installation
============

Requirements
============

- TYPO3 14.3
- typo3/cms-fluid-styled-content (content element rendering; loaded
  automatically as a site set dependency)
- PHP >= 8.2
- Ghostscript >= 10.0
- ImageMagick >= 6.9

Composer
========

Since the package is hosted on a private GitHub repository, you need to
add it as a VCS source and configure authentication.

**1. Add the repository to your project's** ``composer.json``:

.. code-block:: json

    {
        "repositories": [
            {
                "type": "vcs",
                "url": "https://github.com/hohegger/digital-page-flip.git"
            }
        ]
    }

**2. Configure a GitHub token** (only needed once):

.. code-block:: bash

    composer config github-oauth.github.com <your-token>

Create a personal access token at
`GitHub Settings <https://github.com/settings/tokens/new>`__ with the
``repo`` scope.

**3. Set preferred install to dist** (ensures clean packages without
development files):

.. code-block:: bash

    composer config preferred-install dist

**4. Install the extension:**

.. code-block:: bash

    composer require kit/digital-page-flip

**5. Activate:**

.. code-block:: bash

    vendor/bin/typo3 extension:setup
    vendor/bin/typo3 cache:flush

**6. Activate the site set (required):**

Since version 2.0.0 the extension provides its TypoScript (template paths,
plugin settings, storage PID) as a site set instead of a static TypoScript
template. Add the set to every site that uses the flipbook — either in the
:guilabel:`Sites` module under *Sets for this Site*, or directly in
:file:`config/sites/<identifier>/config.yaml`:

.. code-block:: yaml

    dependencies:
      - kit/digital-page-flip

.. attention::

    **Upgrading from 1.x:** The static TypoScript template
    ("Digital Page Flip") no longer exists. Remove the stale include from
    your sys_template record and declare the site set dependency as shown
    above instead. Without it, the flipbook content element renders without
    templates and settings.

System dependencies
===================

The server running TYPO3 must have **Ghostscript** and **ImageMagick**
installed. On Debian/Ubuntu:

.. code-block:: bash

    apt-get install ghostscript imagemagick

Verify the installation:

.. code-block:: bash

    gs --version        # >= 10.0
    convert --version   # >= 6.9
