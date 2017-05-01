Pico Deprecated Plugin
======================

This is the repository of Pico's official `PicoDeprecated` plugin.

Pico is a stupidly simple, blazing fast, flat file CMS. See http://picocms.org/ for more info.

`PicoDeprecated`'s purpose is to maintain backward compatibility to older versions of Pico, by re-introducing characteristics that were removed from Pico's core. It for example triggers old events (like the `before_render` event used before Pico 1.0) and reads config files that were written in PHP (`config/config.php`, used before Pico 2.0).

Please refer to [`picocms/Pico`](https://github.com/picocms/Pico) to get info about how to contribute or getting help.

Install
-------

You don't have to install this plugin manually, it's already a dependency of Pico's core.

The versioning of `PicoDeprecated` follows the major and minor version of Pico's core. You **must not** use a version of `PicoDeprecated` that doesn't match the major and minor version of Pico's core (e.g. v2.1.3 is incompatible with Pico 2.0), i.e. for Pico 2.0 you **must** use a version constraint like `~2.0.0`.

Usage
-----

The plugin tries to guess whether it needs to be enabled or not. Obviously guessing doesn't always work, so you might want to enable or disable the plugin manually by adding `PicoDeprecated.enabled: true` or `PicoDeprecated.enabled: false` to your `config/config.yml`.

Please refer to [`FEATURES.md`](FEATURES.md) for a complete list of all characteristics `PicoDeprecated` restores.
