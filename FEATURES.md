Pico 0.8 and 0.9
----------------

* Defines the constants `ROOT_DIR`, `CONFIG_DIR`, `LIB_DIR`, `PLUGINS_DIR`, `THEMES_DIR`, `CONTENT_DIR` and `CONTENT_EXT`

* Sets the global variable `$config` as reference to Pico's config array

* Reads `config.php` in Pico's root dir and merges its `$config` variable into Pico's config array

* Triggers the following events:

  | Event                 | ... triggers the deprecated event                           |
  | --------------------- | ----------------------------------------------------------- |
  | `onPluginsLoaded`     | `plugins_loaded()`                                          |
  | `onConfigLoaded`      | `config_loaded($config)`                                    |
  | `onRequestUrl`        | `request_url($url)`                                         |
  | `onContentLoading`    | `before_load_content($file)`                                |
  | `onContentLoaded`     | `after_load_content($file, $rawContent)`                    |
  | `on404ContentLoading` | `before_404_load_content($file)`                            |
  | `on404ContentLoaded`  | `after_404_load_content($file, $rawContent)`                |
  | `onMetaHeaders`       | `before_read_file_meta($headers)`                           |
  | `onMetaParsed`        | `file_meta($meta)`                                          |
  | `onContentParsing`    | `before_parse_content($rawContent)`                         |
  | `onContentParsed`     | `after_parse_content($content)`                             |
  | `onContentParsed`     | `content_parsed($content)`                                  |
  | `onSinglePageLoaded`  | `get_page_data($pages, $meta)`                              |
  | `onPagesLoaded`       | `get_pages($pages, $currentPage, $previousPage, $nextPage)`<br><br>Please note that array keys are removed before passing the `$pages` argument. The array gets re-indexed afterwards using the `$page['id']` key, what is derived from `$page['url']` if necessary. |
  | `onTwigRegistration`  | `before_twig_register()`                                    |
  | `onPageRendering`     | `before_render($twigVariables, $twig, $templateName)`<br><br>Please note that the file extension is removed from the `$templateName` argument before passing it to the event. The same file extension is added again afterwards. |
  | `onPageRendered`      | `after_render($output)`                                     |

#### Before `PicoDeprecated` v2.0

* Enables Pico's built-in [`PicoParsePagesContent`](http://picocms.org/plugins/parse-pages-content/) and [`PicoExcerpt`](http://picocms.org/plugins/excerpt/) plugins

Pico 1.0
--------

* Reads `config.php` in Pico's config dir (e.g. `config/config.php`) and merges its `$config` variable into Pico's config array

* Defines the Twig variables `rewrite_url` (replaced by `{{ config.rewrite_url }}`) and `is_front_page` (replaced by `{{ current_page.id == "index" }}`)
