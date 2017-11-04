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
 * `PicoDeprecated` is basically a mandatory plugin for all Pico installs.
 * Without this plugin you can't use plugins which were written for other
 * API versions than the one of Pico's core, even when there was just a
 * absolutely insignificant change.
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
     * API version used by this plugin
     *
     * @var int
     */
    const API_VERSION = 2;

    /**
     * API version of Pico 0.9 and earlier
     *
     * @var int
     */
    const API_VERSION_0_9 = 0;

    /**
     * API version of Pico 1.0
     *
     * @var int
     */
    const API_VERSION_1_0 = 1;

    /**
     * API version of Pico 2.0
     *
     * @var int
     */
    const API_VERSION_2_0 = 2;

    /**
     * Map of core events matching event signatures of older API versions
     *
     * @see self::handleEvent()
     * @var array
     */
    protected $eventAliases = array(
        'onPluginsLoaded' => array(),
        'onPluginManuallyLoaded' => array(),
        'onConfigLoaded' => array(
            array(self::API_VERSION_0_9, 'config_loaded'),
            array(self::API_VERSION_1_0, 'onConfigLoaded')
        ),
        'onRequestUrl' => array(
            array(self::API_VERSION_0_9, 'request_url'),
            array(self::API_VERSION_1_0, 'onRequestUrl')
        ),
        'onRequestFile' => array(
            array(self::API_VERSION_1_0, 'onRequestFile')
        ),
        'onContentLoading' => array(
            array(self::API_VERSION_0_9, 'before_load_content'),
            array(self::API_VERSION_1_0, 'onContentLoading')
        ),
        'on404ContentLoading' => array(
            array(self::API_VERSION_0_9, 'before_404_load_content'),
            array(self::API_VERSION_1_0, 'on404ContentLoading')
        ),
        'on404ContentLoaded' => array(
            array(self::API_VERSION_1_0, 'on404ContentLoaded')
        ),
        'onContentLoaded' => array(
            array(self::API_VERSION_1_0, 'onContentLoaded')
        ),
        'onMetaParsing' => array(),
        'onMetaParsed' => array(
            array(self::API_VERSION_0_9, 'file_meta'),
            array(self::API_VERSION_1_0, 'onMetaParsed')
        ),
        'onContentParsing' => array(
            array(self::API_VERSION_0_9, 'before_parse_content'),
            array(self::API_VERSION_1_0, 'onContentParsing')
        ),
        'onContentPrepared' => array(
            array(self::API_VERSION_1_0, 'onContentPrepared')
        ),
        'onContentParsed' => array(
            array(self::API_VERSION_0_9, 'content_parsed'),
            array(self::API_VERSION_0_9, 'after_parse_content'),
            array(self::API_VERSION_1_0, 'onContentParsed')
        ),
        'onPagesLoading' => array(
            array(self::API_VERSION_1_0, 'onPagesLoading')
        ),
        'onSinglePageLoading' => array(
            array(self::API_VERSION_1_0, 'onSinglePageLoading')
        ),
        'onSinglePageContent' => array(),
        'onSinglePageLoaded' => array(
            array(self::API_VERSION_1_0, 'onSinglePageLoaded')
        ),
        'onPagesDiscovered' => array(),
        'onPagesLoaded' => array(),
        'onCurrentPageDiscovered' => array(),
        'onPageRendering' => array(),
        'onPageRendered' => array(
            array(self::API_VERSION_0_9, 'after_render'),
            array(self::API_VERSION_1_0, 'onPageRendered')
        ),
        'onMetaHeaders' => array(
            array(self::API_VERSION_1_0, 'onMetaHeaders')
        ),
        'onYamlParserRegistered' => array(),
        'onParsedownRegistered' => array(),
        'onTwigRegistered' => array()
    );

    /**
     * Loaded plugins, indexed by API version
     *
     * @see self::onPluginsLoaded()
     * @var array|null
     */
    protected $plugins;

    /**
     * The requested file
     *
     * @see self::onRequestFile()
     * @var string|null
     */
    protected $requestFile;

    /**
     * List of known meta headers
     *
     * @see self::onMetaHeaders()
     * @var string[]|null
     */
    protected $metaHeaders;

    /**
     * List of known pages
     *
     * @see self::onPagesLoaded()
     * @var array[]|null
     */
    protected $pages;

    /**
     * Twig instance used for template parsing
     *
     * @see self::onTwigRegistration()
     * @var Twig_Environment|null
     */
    protected $twig;

    /**
     * @see PicoPluginInterface::handleEvent()
     */
    public function handleEvent($eventName, array $params)
    {
        parent::handleEvent($eventName, $params);

        if ($this->isEnabled()) {
            if (isset($this->eventAliases[$eventName])) {
                // trigger core events matching the event signatures of older API versions
                foreach ($this->eventAliases[$eventName] as $eventAlias) {
                    $this->triggerEvent($eventAlias[0], $eventAlias[1], $params);
                }
            } else {
                // trigger custom events on plugins using API v1 and later
                $this->triggerEvent(self::API_VERSION_1_0, $eventName, $params);
            }
        }
    }

    /**
     * Reads all loaded plugins and indexes them by API level, triggers
     * API v0 event plugins_loaded() and API v1 event onPluginsLoaded($plugins)
     *
     * Please note that the API v1 event `onPluginsLoaded()` originally passed
     * the `$plugins` array by reference. This isn't the case anymore since
     * Pico 2.0. This is a BC-breaking change! The parameter is still passed
     * by reference, but changing it doesn't affect anything.
     *
     * @see self::loadPlugin()
     * @see DummyPlugin::onPluginsLoaded()
     */
    public function onPluginsLoaded(array $plugins)
    {
        foreach ($plugins as $plugin) {
            $this->loadPlugin($plugin);
        }

        $this->triggerEvent(self::API_VERSION_0_9, 'plugins_loaded');
        $this->triggerEvent(self::API_VERSION_1_0, 'onPluginsLoaded', array(&$plugins));
    }

    /**
     * Adds a manually loaded plugin to PicoDeprecated's plugin index
     *
     * @see self::loadPlugin()
     * @see DummyPlugin::onPluginManuallyLoaded()
     */
    public function onPluginManuallyLoaded($plugin)
    {
        $this->loadPlugin($plugin);
    }

    /**
     * Adds a plugin to PicoDeprecated's plugin index to trigger deprecated
     * events by API level
     *
     * @see self::onPluginsLoaded()
     * @see self::onPluginManuallyLoaded()
     *
     * @param object $plugin loaded plugin instance
     *
     * @return void
     */
    protected function loadPlugin($plugin)
    {
        $pluginName = get_class($plugin);

        $apiVersion = self::API_VERSION_0_9;
        if ($plugin instanceof PicoPluginInterface) {
            if (defined($pluginName . '::API_VERSION')) {
                $apiVersion = $pluginName::API_VERSION;
            } else {
                $apiVersion = self::API_VERSION_1_0;
            }
        }

        if (!isset($this->plugins[$apiVersion][$pluginName])) {
            // PicoDeprecated currently supports all previous API versions
            $this->plugins[$apiVersion][$pluginName] = $plugin;
        }
    }

    /**
     * Re-introduces various config-related characteristics
     *
     * 1. Defines various config-related constants
     *    ({@see self::defineConstants()})
     * 2. Reads `config.php` in Pico's config dir (`config/config.php`)
     *    ({@see self::loadScriptedConfig()})
     * 3. Reads `config.php` in Pico's root dir
     *    ({@see self::loadRootDirConfig()})
     * 4. Defines the global `$config` variable
     *
     * @see self::defineConstants()
     * @see self::loadScriptedConfig()
     * @see self::loadRootDirConfig()
     * @see DummyPlugin::onConfigLoaded()
     */
    public function onConfigLoaded(array &$config)
    {
        $this->defineConstants();
        $this->loadScriptedConfig($config);
        $this->loadRootDirConfig($config);

        if (!isset($GLOBALS['config'])) {
            $GLOBALS['config'] = &$config;
        }
    }

    /**
     * Defines various config-related constants
     *
     * `ROOT_DIR`, `LIB_DIR`, `PLUGINS_DIR`, `THEMES_DIR` and `CONTENT_EXT`
     * were removed in v1.0, `CONTENT_DIR` existed just in v0.9,
     * `CONFIG_DIR` just for a short time between v0.9 and v1.0 and
     * `CACHE_DIR` was dropped with v1.0 without a replacement.
     *
     * @see self::onConfigLoaded()
     *
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
     * Reads a config.php in Pico's config dir (i.e. config/config.php)
     *
     * @see self::onConfigLoaded()
     * @see Pico::loadConfig()
     *
     * @param array &$realConfig array of config variables
     *
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
     * Reads a config.php in Pico's root dir
     *
     * @see self::onConfigLoaded()
     * @see Pico::loadConfig()
     *
     * @param array &$realConfig array of config variables
     *
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
     * Sets self::$requestFile
     *
     * @see DummyPlugin::onRequestFile()
     */
    public function onRequestFile(&$file)
    {
        $this->requestFile = &$file;
    }

    /**
     * Triggers API v0 event after_load_content($file, $rawContent)
     *
     * @see self::onRequestFile()
     * @see DummyPlugin::onContentLoaded()
     */
    public function onContentLoaded(&$rawContent)
    {
        $this->triggerEvent(self::API_VERSION_0_9, 'after_load_content', array(&$this->requestFile, &$rawContent));
    }

    /**
     * Triggers API v0 event after_404_load_content($file, $rawContent)
     *
     * @see self::onRequestFile()
     * @see DummyPlugin::on404ContentLoaded()
     */
    public function on404ContentLoaded(&$rawContent)
    {
        $this->triggerEvent(self::API_VERSION_0_9, 'after_404_load_content', array(&$this->requestFile, &$rawContent));
    }

    /**
     * Triggers API v0 event before_read_file_meta($metaHeaders) and
     * API v1 event onMetaParsing($rawContent, $metaHeaders)
     *
     * @see self::onMetaHeaders()
     * @see DummyPlugin::onMetaParsing()
     */
    public function onMetaParsing(&$rawContent)
    {
        // make sure to trigger the onMetaHeaders event
        $this->getMetaHeaders();

        $this->triggerEvent(self::API_VERSION_0_9, 'before_read_file_meta', array(&$this->metaHeaders));
        $this->triggerEvent(self::API_VERSION_1_0, 'onMetaParsing', array(&$rawContent, &$this->metaHeaders));
    }

    /**
     * Triggers API v0 event get_page_data($pages, $meta)
     *
     * @see DummyPlugin::onSinglePageLoaded()
     */
    public function onSinglePageLoaded(array &$pageData)
    {
        $this->triggerEvent(self::API_VERSION_0_9, 'get_page_data', array(&$pageData, $pageData['meta']));
    }

    /**
     * Sets self::$pages
     *
     * @see DummyPlugin::onPagesLoaded()
     */
    public function onPagesLoaded(array &$pages)
    {
        $this->pages = &$pages;
    }

    /**
     * Triggers API v0 event get_pages(...) and API v1 event
     * onPagesLoaded($pages, $currentPage, $previousPage, $nextPage)
     *
     * Please note that the `get_pages()` event gets `$pages` passed without a
     * array index. The index is rebuild later using either the `id` array key
     * or is derived from the `url` array key. If it isn't possible to derive
     * the array key, `~unknown` is being used. Duplicates are prevented by
     * adding `~dup` when necessary.
     *
     * @see self::onPagesLoaded()
     * @see DummyPlugin::onPagesLoaded()
     */
    public function onCurrentPageDiscovered(
        array &$currentPage = null,
        array &$previousPage = null,
        array &$nextPage = null
    ) {
        // trigger API v0 event
        if ($this->triggersApiEvents(self::API_VERSION_0_9)) {
            // remove keys of pages array
            $plainPages = array();
            foreach ($this->pages as &$plainPageData) {
                $plainPages[] = &$plainPageData;
            }

            // trigger event
            $this->triggerEvent(
                self::API_VERSION_0_9,
                'get_pages',
                array(&$plainPages, &$currentPage, &$previousPage, &$nextPage)
            );

            // re-index pages array
            $this->pages = array();
            foreach ($plainPages as &$pageData) {
                if (!isset($pageData['id'])) {
                    $baseUrlLength = strlen($this->getBaseUrl());
                    if (substr($pageData['url'], 0, $baseUrlLength) === $this->getBaseUrl()) {
                        if ($this->isUrlRewritingEnabled() && (substr($pageData['url'], $baseUrlLength, 1) === '?')) {
                            $pageData['id'] = substr($pageData['url'], $baseUrlLength + 1);
                        } else {
                            $pageData['id'] = substr($pageData['url'], $baseUrlLength);
                        }
                    } else {
                        // foreign URLs lead to ~unknown, ~unknown~dup1, ~unknown~dup2, ...
                        $pageData['id'] = '~unknown';
                    }
                }

                // prevent duplicates
                $id = $pageData['id'];
                for ($i = 1; isset($this->pages[$id]); $i++) {
                    $id = $pageData['id'] . '~dup' . $i;
                }

                $this->pages[$id] = &$pageData;
            }
        }

        // trigger API v1 event
        $this->triggerEvent(
            self::API_VERSION_1_0,
            'onPagesLoaded',
            array(&$this->pages, &$currentPage, &$previousPage, &$nextPage)
        );
    }

    /**
     * Triggers API v0 event before_render($twigVariables, $twig, $templateName)
     * and adds Twig template variables rewrite_url and is_front_page
     *
     * Please note that the `before_render()` event gets `$templateName` passed
     * without its file extension. The file extension is later added again.
     *
     * @see self::onTwigRegistered()
     * @see DummyPlugin::onPageRendering()
     */
    public function onPageRendering(&$templateName, array &$twigVariables)
    {
        // rewrite_url and is_front_page are deprecated since Pico 2.0
        if (!isset($twigVariables['rewrite_url'])) {
            $twigVariables['rewrite_url'] = $this->isUrlRewritingEnabled();
        }
        if (!isset($twigVariables['is_front_page'])) {
            $frontPage = $this->getConfig('content_dir') . 'index' . $this->getConfig('content_ext');
            $twigVariables['is_front_page'] = ($this->getRequestFile() === $frontPage);
        }

        // make sure to trigger the onTwigRegistered event
        $this->getTwig();

        // trigger API v0 event
        if ($this->triggersApiEvents(self::API_VERSION_0_9)) {
            // template name contains file extension since Pico 1.0
            $fileExtension = '';
            if (($fileExtensionPos = strrpos($templateName, '.')) !== false) {
                $fileExtension = substr($templateName, $fileExtensionPos);
                $templateName = substr($templateName, 0, $fileExtensionPos);
            }

            // trigger event
            $this->triggerEvent(
                self::API_VERSION_0_9,
                'before_render',
                array(&$twigVariables, &$this->twig, &$templateName)
            );

            // add original file extension
            // we assume that all templates of a theme use the same file extension
            $templateName = $templateName . $fileExtension;
        }

        // trigger API v1 event
        $this->triggerEvent(
            self::API_VERSION_1_0,
            'onPageRendering',
            array(&$this->twig, &$twigVariables, &$templateName)
        );
    }

    /**
     * Sets self::$metaHeaders
     *
     * @see DummyPlugin::onMetaHeaders()
     */
    public function onMetaHeaders(array &$headers)
    {
        $this->metaHeaders = &$headers;
    }

    /**
     * Sets self::$twig, triggers API v0 event before_twig_register() and
     * API v1 event onTwigRegistration()
     *
     * @see DummyPlugin::onTwigRegistered()
     */
    public function onTwigRegistered(Twig_Environment &$twig)
    {
        $this->twig = $twig;

        $this->triggerEvent(self::API_VERSION_0_9, 'before_twig_register');
        $this->triggerEvent(self::API_VERSION_1_0, 'onTwigRegistration');
    }

    /**
     * Returns whether events of a particular API level are triggered or not
     *
     * @param int $apiVersion API version to check
     *
     * @return boolean TRUE if PicoDeprecated triggers events of this API level,
     *     FALSE otherwise
     */
    public function triggersApiEvents($apiVersion)
    {
        return isset($this->plugins[$apiVersion]);
    }

    /**
     * Triggers deprecated events on plugins of different API versions
     *
     * Please note that events of a specific API version are only triggered
     * on plugins with this particular API version. Deprecated events of
     * API v0 are also triggered on plugins using API v1.
     *
     * You can use this public method in other plugins to trigger custom events
     * on plugins using a particular API version. If you want to trigger a
     * custom event on all plugins, no matter their API version (except for
     * plugins using API v0), use {@see Pico::triggerEvent()} instead.
     *
     * @see Pico::triggerEvent()
     *
     * @param int    $apiVersion API version of the event
     * @param string $eventName  event to trigger
     * @param array  $params     parameters to pass
     *
     * @return void
     */
    public function triggerEvent($apiVersion, $eventName, array $params = array())
    {
        if (!isset($this->plugins[$apiVersion])) {
            return;
        }

        // API v0
        if ($apiVersion === self::API_VERSION_0_9) {
            // API v0 events are also triggered on plugins using API v1 (but not later)
            $plugins = $this->plugins[self::API_VERSION_0_9];
            if (isset($this->plugins[self::API_VERSION_1_0])) {
                $plugins = array_merge($plugins, $this->plugins[self::API_VERSION_1_0]);
            }

            foreach ($plugins as $plugin) {
                if (method_exists($plugin, $eventName)) {
                    call_user_func_array(array($plugin, $eventName), $params);
                }
            }

            return;
        }

        // API v1 and later
        foreach ($this->plugins[$apiVersion] as $plugin) {
            $plugin->handleEvent($eventName, $params);
        }
    }
}
