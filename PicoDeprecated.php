<?php

/**
 * Maintain backward compatibility to older Pico releases
 *
 * `PicoDeprecated`'s purpose is to maintain backward compatibility to older
 * versions of Pico, by re-introducing characteristics that were removed from
 * Pico's core. It for example triggers old events (like the `before_render`
 * event used before Pico 1.0) and reads config files that were written in
 * PHP ({@path "config/config.php"}, used before Pico 2.0).
 *
 * {@see http://picocms.org/plugins/deprecated/} for a full list of features.
 *
 * @author  Daniel Rudolf
 * @link    http://picocms.org
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 2.0
 */
class PicoDeprecated extends AbstractPicoPlugin
{
    /**
     * This plugin is disabled by default
     *
     * @see AbstractPicoPlugin::$enabled
     */
    protected $enabled = false;

    /**
     * The requested file
     *
     * @see PicoDeprecated::getRequestFile()
     * @var string|null
     */
    protected $requestFile;

    /**
     * Enables this plugin on demand and triggers the deprecated event
     * plugins_loaded()
     *
     * @see DummyPlugin::onPluginsLoaded()
     */
    public function onPluginsLoaded(array &$plugins)
    {
        if ($plugins) {
            foreach ($plugins as $plugin) {
                if (!($plugin instanceof PicoPluginInterface)) {
                    // the plugin doesn't implement PicoPluginInterface; it uses deprecated events
                    // enable PicoDeprecated if it hasn't be explicitly enabled/disabled yet
                    if (!$this->isStatusChanged()) {
                        $this->setEnabled(true, true, true);
                    }
                    break;
                }
            }
        } else {
            // no plugins were found, so it actually isn't necessary to call deprecated events
            // anyway, this plugin also ensures compatibility apart from events used by old plugins,
            // so enable PicoDeprecated if it hasn't be explicitly enabled/disabled yet
            if (!$this->isStatusChanged()) {
                $this->setEnabled(true, true, true);
            }
        }

        if ($this->isEnabled()) {
            $this->triggerEvent('plugins_loaded');
        }
    }

    /**
     * Triggers the deprecated event config_loaded($config)
     *
     * This method also defines deprecated constants, reads the `config.php`
     * in Pico's root dir, enables the plugins {@link PicoParsePagesContent}
     * and {@link PicoExcerpt} and makes `$config` globally accessible (the
     * latter was removed with Pico 0.9 and was added again as deprecated
     * feature with Pico 1.0)
     *
     * @see    PicoDeprecated::defineConstants()
     * @see    PicoDeprecated::loadRootDirConfig()
     * @see    PicoDeprecated::enablePlugins()
     * @see    DummyPlugin::onConfigLoaded()
     * @param  array &$config array of config variables
     * @return void
     */
    public function onConfigLoaded(array &$config)
    {
        $this->defineConstants();
        $this->loadScriptedConfig($config);
        $this->loadRootDirConfig($config);
        $this->enablePlugins();
        $GLOBALS['config'] = &$config;

        $this->triggerEvent('config_loaded', array(&$config));
    }

    /**
     * Defines deprecated constants
     *
     * `ROOT_DIR`, `LIB_DIR`, `PLUGINS_DIR`, `THEMES_DIR` and `CONTENT_EXT`
     * are deprecated since v1.0, `CONTENT_DIR` existed just in v0.9,
     * `CONFIG_DIR` just for a short time between v0.9 and v1.0 and
     * `CACHE_DIR` was dropped with v1.0 without a replacement.
     *
     * @see    PicoDeprecated::onConfigLoaded()
     * @return void
     */
    protected function defineConstants()
    {
        if (!defined('ROOT_DIR')) {
            define('ROOT_DIR', $this->getRootDir());
        }
        if (!defined('CONFIG_DIR')) {
            define('CONFIG_DIR', $this->getConfigDir());
        }
        if (!defined('LIB_DIR')) {
            $picoReflector = new ReflectionClass('Pico');
            define('LIB_DIR', dirname($picoReflector->getFileName()) . '/');
        }
        if (!defined('PLUGINS_DIR')) {
            define('PLUGINS_DIR', $this->getPluginsDir());
        }
        if (!defined('THEMES_DIR')) {
            define('THEMES_DIR', $this->getThemesDir());
        }
        if (!defined('CONTENT_DIR')) {
            define('CONTENT_DIR', $this->getConfig('content_dir'));
        }
        if (!defined('CONTENT_EXT')) {
            define('CONTENT_EXT', $this->getConfig('content_ext'));
        }
    }

    /**
     * Read config.php in Pico's config dir (i.e. config/config.php)
     *
     * @see    PicoDeprecated::onConfigLoaded()
     * @see    Pico::loadConfig()
     * @param  array &$realConfig array of config variables
     * @return void
     */
    protected function loadScriptedConfig(array &$realConfig)
    {
        if (file_exists($this->getConfigDir() . 'config.php')) {
            // scope isolated require()
            $includeClosure = function ($configFile) {
                require($configFile);
                return (isset($config) && is_array($config)) ? $config : array();
            };
            if (PHP_VERSION_ID >= 50400) {
                $includeClosure = $includeClosure->bindTo(null);
            }

            // config.php in Pico::$configDir (i.e. config/config.php) is deprecated
            // use *.yml files in Pico::$configDir instead
            $config = $includeClosure($this->getConfigDir() . 'config.php');

            if ($config) {
                if (!empty($config['base_url'])) {
                    $config['base_url'] = rtrim($config['base_url'], '/') . '/';
                }
                if (!empty($config['content_dir'])) {
                    $config['content_dir'] = $this->getAbsolutePath($config['content_dir']);
                }
                if (!empty($config['theme_url'])) {
                    if (preg_match('#^[A-Za-z][A-Za-z0-9+\-.]*://#', $config['theme_url'])) {
                        $config['theme_url'] = rtrim($config['theme_url'], '/') . '/';
                    } else {
                        $config['theme_url'] = $this->getBaseUrl() . rtrim($config['theme_url'], '/') . '/';
                    }
                }
                if (!empty($config['timezone'])) {
                    date_default_timezone_set($config['timezone']);
                }

                $realConfig = $config + $realConfig;
            }
        }
    }

    /**
     * Read config.php in Pico's root dir
     *
     * @see    PicoDeprecated::onConfigLoaded()
     * @see    Pico::loadConfig()
     * @param  array &$realConfig array of config variables
     * @return void
     */
    protected function loadRootDirConfig(array &$realConfig)
    {
        if (file_exists($this->getRootDir() . 'config.php')) {
            $config = null;

            // scope isolated require()
            $includeClosure = function ($configFile) use (&$config) {
                require($configFile);
            };
            if (PHP_VERSION_ID >= 50400) {
                $includeClosure = $includeClosure->bindTo(null);
            }

            // config.php in Pico::$rootDir is deprecated
            // use config.php in Pico::$configDir instead
            $includeClosure($this->getRootDir() . 'config.php');

            if (is_array($config)) {
                if (isset($config['base_url'])) {
                    $config['base_url'] = rtrim($config['base_url'], '/') . '/';
                }
                if (isset($config['content_dir'])) {
                    $config['content_dir'] = rtrim($config['content_dir'], '/\\') . '/';
                }

                $realConfig = $config + $realConfig;
            }
        }
    }

    /**
     * Enables the plugins PicoParsePagesContent and PicoExcerpt
     *
     * @see    PicoParsePagesContent
     * @see    PicoExcerpt
     * @return void
     */
    protected function enablePlugins()
    {
        // enable PicoParsePagesContent and PicoExcerpt
        // we can't enable them during onPluginsLoaded because we can't know
        // if the user disabled us (PicoDeprecated) manually in the config
        $plugins = $this->getPlugins();
        if (isset($plugins['PicoParsePagesContent'])) {
            // parse all pages content if this plugin hasn't
            // be explicitly enabled/disabled yet
            if (!$plugins['PicoParsePagesContent']->isStatusChanged()) {
                $plugins['PicoParsePagesContent']->setEnabled(true, true, true);
            }
        }
        if (isset($plugins['PicoExcerpt'])) {
            // enable excerpt plugin if it hasn't be explicitly enabled/disabled yet
            if (!$plugins['PicoExcerpt']->isStatusChanged()) {
                $plugins['PicoExcerpt']->setEnabled(true, true, true);
            }
        }
    }

    /**
     * Triggers the deprecated event request_url($url)
     *
     * @see DummyPlugin::onRequestUrl()
     */
    public function onRequestUrl(&$url)
    {
        $this->triggerEvent('request_url', array(&$url));
    }

    /**
     * Sets PicoDeprecated::$requestFile to trigger the deprecated
     * events after_load_content() and after_404_load_content()
     *
     * @see PicoDeprecated::onContentLoaded()
     * @see PicoDeprecated::on404ContentLoaded()
     * @see DummyPlugin::onRequestFile()
     */
    public function onRequestFile(&$file)
    {
        $this->requestFile = &$file;
    }

    /**
     * Triggers the deprecated before_load_content($file)
     *
     * @see DummyPlugin::onContentLoading()
     */
    public function onContentLoading(&$file)
    {
        $this->triggerEvent('before_load_content', array(&$file));
    }

    /**
     * Triggers the deprecated event after_load_content($file, $rawContent)
     *
     * @see DummyPlugin::onContentLoaded()
     */
    public function onContentLoaded(&$rawContent)
    {
        $this->triggerEvent('after_load_content', array(&$this->requestFile, &$rawContent));
    }

    /**
     * Triggers the deprecated before_404_load_content($file)
     *
     * @see DummyPlugin::on404ContentLoading()
     */
    public function on404ContentLoading(&$file)
    {
        $this->triggerEvent('before_404_load_content', array(&$file));
    }

    /**
     * Triggers the deprecated event after_404_load_content($file, $rawContent)
     *
     * @see DummyPlugin::on404ContentLoaded()
     */
    public function on404ContentLoaded(&$rawContent)
    {
        $this->triggerEvent('after_404_load_content', array(&$this->requestFile, &$rawContent));
    }

    /**
     * Triggers the deprecated event before_read_file_meta($headers)
     *
     * @see DummyPlugin::onMetaHeaders()
     */
    public function onMetaHeaders(array &$headers)
    {
        $this->triggerEvent('before_read_file_meta', array(&$headers));
    }

    /**
     * Triggers the deprecated event file_meta($meta)
     *
     * @see DummyPlugin::onMetaParsed()
     */
    public function onMetaParsed(array &$meta)
    {
        $this->triggerEvent('file_meta', array(&$meta));
    }

    /**
     * Triggers the deprecated event before_parse_content($rawContent)
     *
     * @see DummyPlugin::onContentParsing()
     */
    public function onContentParsing(&$rawContent)
    {
        $this->triggerEvent('before_parse_content', array(&$rawContent));
    }

    /**
     * Triggers the deprecated events after_parse_content($content) and
     * content_parsed($content)
     *
     * @see DummyPlugin::onContentParsed()
     */
    public function onContentParsed(&$content)
    {
        $this->triggerEvent('after_parse_content', array(&$content));

        // deprecated since v0.8
        $this->triggerEvent('content_parsed', array(&$content));
    }

    /**
     * Triggers the deprecated event get_page_data($pages, $meta)
     *
     * @see DummyPlugin::onSinglePageLoaded()
     */
    public function onSinglePageLoaded(array &$pageData)
    {
        $this->triggerEvent('get_page_data', array(&$pageData, $pageData['meta']));
    }

    /**
     * Triggers the deprecated event
     * get_pages($pages, $currentPage, $previousPage, $nextPage)
     *
     * Please note that the `get_pages()` event gets `$pages` passed without a
     * array index. The index is rebuild later using either the `id` array key
     * or is derived from the `url` array key. Duplicates are prevented by
     * adding `~dup` when necessary.
     *
     * @see DummyPlugin::onPagesLoaded()
     */
    public function onPagesLoaded(
        array &$pages,
        array &$currentPage = null,
        array &$previousPage = null,
        array &$nextPage = null
    ) {
        // remove keys of pages array
        $plainPages = array();
        foreach ($pages as &$pageData) {
            $plainPages[] = &$pageData;
        }
        unset($pageData);

        $this->triggerEvent('get_pages', array(&$plainPages, &$currentPage, &$previousPage, &$nextPage));

        // re-index pages array
        $pages = array();
        foreach ($plainPages as &$pageData) {
            if (!isset($pageData['id'])) {
                $urlPrefixLength = strlen($this->getBaseUrl()) + intval(!$this->isUrlRewritingEnabled());
                $pageData['id'] = substr($pageData['url'], $urlPrefixLength);
            }

            // prevent duplicates
            $id = $pageData['id'];
            for ($i = 1; isset($pages[$id]); $i++) {
                $id = $pageData['id'] . '~dup' . $i;
            }

            $pages[$id] = &$pageData;
        }
    }

    /**
     * Triggers the deprecated event before_twig_register()
     *
     * @see DummyPlugin::onTwigRegistration()
     */
    public function onTwigRegistration()
    {
        $this->triggerEvent('before_twig_register');
    }

    /**
     * Adds the deprecated variables rewrite_url and is_front_page, triggers
     * the deprecated event before_render($twigVariables, $twig, $templateName)
     *
     * Please note that the `before_render()` event gets `$templateName` passed
     * without its file extension. The file extension is later added again.
     *
     * @see DummyPlugin::onPageRendering()
     */
    public function onPageRendering(Twig_Environment &$twig, array &$twigVariables, &$templateName)
    {
        // rewrite_url and is_front_page are deprecated since Pico 2.0
        if (!isset($twigVariables['rewrite_url'])) {
            $twigVariables['rewrite_url'] = $this->isUrlRewritingEnabled();
        }

        if (!isset($twigVariables['is_front_page'])) {
            $frontPage = $this->getConfig('content_dir') . 'index' . $this->getConfig('content_ext');
            $twigVariables['is_front_page'] = ($this->getRequestFile() === $frontPage);
        }

        // template name contains file extension since Pico 1.0
        $fileExtension = '';
        if (($fileExtensionPos = strrpos($templateName, '.')) !== false) {
            $fileExtension = substr($templateName, $fileExtensionPos);
            $templateName = substr($templateName, 0, $fileExtensionPos);
        }

        $this->triggerEvent('before_render', array(&$twigVariables, &$twig, &$templateName));

        // add original file extension
        $templateName = $templateName . $fileExtension;
    }

    /**
     * Triggers the deprecated event after_render($output)
     *
     * @see DummyPlugin::onPageRendered()
     */
    public function onPageRendered(&$output)
    {
        $this->triggerEvent('after_render', array(&$output));
    }

    /**
     * Triggers a deprecated event on all plugins
     *
     * Deprecated events are also triggered on plugins which implement
     * {@link PicoPluginInterface}. Please note that the methods are called
     * directly and not through {@link PicoPluginInterface::handleEvent()}.
     *
     * @param  string $eventName event to trigger
     * @param  array  $params    parameters to pass
     * @return void
     */
    protected function triggerEvent($eventName, array $params = array())
    {
        foreach ($this->getPlugins() as $plugin) {
            if (method_exists($plugin, $eventName)) {
                call_user_func_array(array($plugin, $eventName), $params);
            }
        }
    }
}
