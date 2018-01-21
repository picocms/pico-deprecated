Pico Deprecated Plugin
======================

This is the repository of Pico's official `PicoDeprecated` plugin.

Pico is a stupidly simple, blazing fast, flat file CMS. See http://picocms.org/ for more info.

`PicoDeprecated`'s purpose is to maintain backward compatibility to older versions of Pico, by re-introducing characteristics that were removed from Pico's core. It for example triggers old events (like the `before_render` event used before Pico 1.0) and reads config files that were written in PHP (`config/config.php`, used before Pico 2.0).

Please refer to [`picocms/Pico`](https://github.com/picocms/Pico) to get info about how to contribute or getting help.

Install
-------

You usually don't have to install this plugin manually, it's shipped together with [Pico's pre-built release packages](https://github.com/picocms/Pico/releases/latest) and a default dependency of [`picocms/pico-composer`](https://github.com/picocms/pico-composer).

If you're using plugins and themes that are compatible with Pico's latest API version only, you can safely remove `PicoDeprecated` from your Pico installation or disable the plugin (please refer to the "Usage" section below). However, if you're not sure about this, simply leave it as it is - it won't hurt... :wink:

If you use a `composer`-based installation of Pico and want to either remove or install `PicoDeprecated`, simply open a shell on your server and navigate to Pico's install directory (e.g. `/var/www/html`). Run `composer remove picocms/pico-deprecated` to remove `PicoDeprecated`, or run `composer require picocms/pico-deprecated` (via [Packagist.org](https://packagist.org/packages/picocms/pico-deprecated)) to install `PicoDeprecated`.

If you rather use one of Pico's pre-built release packages, it is best to disable `PicoDeprecated` and not to actually remove it. The reason for this is, that `PicoDeprecated` is part of Pico's pre-built release packages, thus it will be automatically re-installed when updating Pico. However, if you really want to remove `PicoDeprecated`, simply delete the `plugins/PicoDeprecated` directory in Pico's install directory (e.g. `/var/www/html`). If you want to install `PicoDeprecated`, you must first create a empty `plugins/PicoDeprecated` directory on your server, [download the version of `PicoDeprecated`](https://github.com/picocms/pico-deprecated/releases) matching the version of your Pico installation and upload all containing files (esp. `PicoDeprecated.php`) into said `plugins/PicoDeprecated` directory (resulting in `plugins/PicoDeprecated/PicoDeprecated.php`).

The versioning of `PicoDeprecated` strictly follows the version of Pico's core. You *must not* use a version of `PicoDeprecated` that doesn't match the version of Pico's core (e.g. PicoDeprecated 2.0.1 is *not compatible* with Pico 2.0.0). If you're using a `composer`-based installation of Pico, simply use a version constaint like `^2.0` - `PicoDeprecated` ensures that its version matches Pico's version. Even if you're using one of Pico's pre-built release packages, you don't have to take care of anything - a matching version of `PicoDeprecated` is part of Pico's pre-built release packages anyway.

Usage
-----

You can explicitly disable `PicoDeprecated` by adding `PicoDeprecated.enabled: false` to your `config/config.yml`. If you want to re-enable `PicoDeprecated`, simply remove this line from your `config/config.yml`. `PicoDeprecated` itself has no configuration options, it enables and disables all of its features depending on whether there are plugins requiring said characteristics.

Getting Help
------------

Please refer to the ["Getting Help" section](https://github.com/picocms/Pico#getting-help) of our main repository.

Contributing
------------

Please refer to the ["Contributing" section](https://github.com/picocms/Pico#contributing) of our main repository.

By contributing to Pico, you accept and agree to the *Developer Certificate of Origin* for your present and future contributions submitted to Pico. Please refer to the ["Developer Certificate of Origin" section](https://github.com/picocms/Pico/blob/master/CONTRIBUTING.md#developer-certificate-of-origin) in the `CONTRIBUTING.md` of our main repository.
