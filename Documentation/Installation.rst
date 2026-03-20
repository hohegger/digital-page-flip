.. _installation:

============
Installation
============

Requirements
============

- TYPO3 12.4 - 13.4
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

    composer require kit/digital-page-flip:^1.0

**5. Activate:**

.. code-block:: bash

    vendor/bin/typo3 extension:setup
    vendor/bin/typo3 cache:flush

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
