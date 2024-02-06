Minify for Symfony!
===================

This bundle makes it easy to use `Minify <https://github.com/tdewolff/minify>`_ with
Symfony's `AssetMapper Component <https://symfony.com/doc/current/frontend/asset_mapper.html>`_
(no Node required!).

- Automatically downloads the correct `Minify binary <https://github.com/tdewolff/minify/tree/master/cmd/minify>`_;
- Adds a ``minify:run`` command to build & watch for changes;
- Transparently swaps in the minified assets.

Installation
------------

Install the bundle & initialize your app with two commands:

.. code-block:: terminal

    $ composer require wgg/minify-bundle

Usage
-----

To use the minified assets tun the command:

.. code-block:: terminal

    $ php bin/console minify:run --watch

That's it! This will watch for changes to your ``assets/`` directory
and automatically minify the files when needed. If you refresh the page, the
final asset files will already contain the minified content.

Symfony CLI
~~~~~~~~~~~

If using the `Symfony CLI <https://symfony.com/download>`_, you can add build
command as a `worker <https://symfony.com/doc/current/setup/symfony_server.html#configuring-workers>`_
to be started whenever you run ``symfony server:start``:

.. code-block:: yaml

    # .symfony.local.yaml
    workers:
        # ...

        minify:
            cmd: ['symfony', 'console', 'minify:run', '--watch']
            watch: ['assets']

.. tip::

    If running ``symfony server:start`` as a daemon, you can run
    ``symfony server:log`` to tail the output of the worker.

How Does It Work?
-----------------

The first time you run the Minify command, the bundle will
download the correct Minify binary for your system into a ``var/minify/``
directory.

When you run ``minify:run``, that binary is used to minify
your assets. Finally, when the contents of an asset is requested, the bundle
swaps the contents of that file with the contents of the minified version.
Nice!

Deploying
---------

When you deploy, run the ``minify:run`` command *before* the ``asset-map:compile``
command so the minifed files area available:

.. code-block:: terminal

    $ php bin/console minify:run
    $ php bin/console asset-map:compile

Configuration
-------------

To see the full config from this bundle, run:

.. code-block:: terminal

    $ php bin/console config:dump wgg_minify

Minify assets options
---------------------

.. code-block:: yaml

    # config/packages/wgg_minify.yaml
    wgg_minify:
        # The directory where the assets can be found
        # assets_directory: '%kernel.project_dir%/assets'

        # Extensions to minify
        # extensions: ['js', 'css']

        # Paths to exclude from minification (paths are relative to assets_directory)
        # excluded_paths: ['vendor']

Usage with StimulusBundle
---------------------

If you plan to use `StimulusBundle Lazy Controllers <https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers>`_
feature, then you must add the `stimulusFetch` comment as follows (note the exclamation mark):

.. code-block:: javascript

    import { Controller } from '@hotwired/stimulus';

    /*! stimulusFetch: 'lazy' */
    export default class extends Controller {
        // ...
    }

Minify binary removes all comments, except the ones that are marked with an exclamation mark.
The bundle default configuration converts these comments back to normal comments, so the StimulusBundle
can generate the Lazy Controllers as intended.

This feature can be turned of in the configuration:

.. code-block:: yaml

    # config/packages/wgg_minify.yaml
    wgg_minify:
        # Disable converting comments
        convert_comments: false


Using a Different Binary
------------------------

To instruct the bundle to use a custom binary, set the ``binary`` option:

.. code-block:: yaml

    # config/packages/wgg_minify.yaml
    wgg_minify:
        binary: 'path_to/minify'

Using a Different Binary Version
------------------------

By default the latest standalone Minify binary gets downloaded. However,
if you want to use a different version, you can specify the version to use,
set ``binary_version`` option:

.. code-block:: yaml

    # config/packages/wgg_minify.yaml
    wgg_minify:
        binary_version: 'v2.0.0'

The ``minify:run`` command will download and use the specified version.
