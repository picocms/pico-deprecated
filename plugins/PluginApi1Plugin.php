<?php
/**
 * This file is part of Pico. It's copyrighted by the contributors recorded
 * in the version control history of the file, available from the following
 * original location:
 *
 * <https://github.com/picocms/pico-deprecated/blob/master/plugins/PluginApi1Plugin.php>
 *
 * This file was created by splitting up an original file into multiple files,
 * which in turn was previously part of the project's main repository. The
 * version control history of these files apply accordingly, available from
 * the following original locations:
 *
 * <https://github.com/picocms/pico-deprecated/blob/90ea3d5a9767f1511f165e051dd7ffb8f1b3f92e/PicoDeprecated.php>
 * <https://github.com/picocms/Pico/blob/82a342ba445122182b898a2c1800f03c8d16f18c/plugins/00-PicoDeprecated.php>
 *
 * SPDX-License-Identifier: MIT
 * License-Filename: LICENSE
 */

namespace picocms\PicoDeprecated\Plugin;

use picocms\PicoDeprecated\AbstractPluginApiPlugin;
use PicoDeprecated;
use Twig\Environment as TwigEnvironment;

/**
 * Maintains backward compatibility with plugins using API version 1, written
 * for Pico 1.0
 *
 * @author  Daniel Rudolf
 * @link    https://picocms.org
 * @license https://opensource.org/licenses/MIT The MIT License
 * @version 3.0
 */
class PluginApi1Plugin extends AbstractPluginApiPlugin
{
    /**
     * This plugin extends {@see PluginApi2Plugin} and {@see ThemeApi1Plugin}
     *
     * @var string[]
     */
    protected $dependsOn = [ PluginApi2Plugin::class, ThemeApi1Plugin::class ];

    /**
     * Map of core events matching event signatures of older API versions
     *
     * @see AbstractPluginApiPlugin::handleEvent()
     *
     * @var array<string,string>
     */
    protected $eventAliases = [
        'onConfigLoaded' =>     [ 'onConfigLoaded' ],
        'onRequestUrl' =>       [ 'onRequestUrl' ],
        'onRequestFile' =>      [ 'onRequestFile' ],
        'on404ContentLoaded' => [ 'on404ContentLoaded' ],
        'onContentLoaded' =>    [ 'onContentLoaded' ],
        'onContentPrepared' =>  [ 'onContentPrepared' ],
        'onContentParsed' =>    [ 'onContentParsed' ],
        'onPagesLoading' =>     [ 'onPagesLoading' ],
        'onSinglePageLoaded' => [ 'onSinglePageLoaded' ],
        'onPageRendered' =>     [ 'onPageRendered' ],
    ];

    /**
     * Pico's request file
     *
     * @see Pico::$requestFile
     * @see PluginApi1Plugin::onRequestFile()
     *
     * @var string|null
     */
    protected $requestFile;

    /**
     * Pico's raw contents
     *
     * @see Pico::$rawContent
     * @see PluginApi1Plugin::onContentLoaded()
     *
     * @var string|null
     */
    protected $rawContent;

    /**
     * Pico's meta headers array
     *
     * @see Pico::$metaHeaders
     * @see PluginApi1Plugin::onMetaHeaders()
     *
     * @var array<string,string>|null
     */
    protected $metaHeaders;

    /**
     * Pico's pages array
     *
     * @see Pico::$pages
     * @see PluginApi1Plugin::onPagesLoaded()
     *
     * @var array[]|null
     */
    protected $pages;

    /**
     * Pico's Twig instance
     *
     * @see Pico::$twig
     * @see PluginApi1Plugin::onTwigRegistered()
     *
     * @var TwigEnvironment|null
     */
    protected $twig;

    /**
     * Triggers the onPluginsLoaded event
     *
     * Prior to API v2 the event `onPluginsLoaded` passed the `$plugins` array
     * by reference. This is no longer the case. We still pass the parameter by
     * reference and use {@see Pico::loadPlugin()} to load additional plugins,
     * however, unloading or replacing plugins was removed without a
     * replacement. This might be a BC-breaking change for you!
     *
     * @param object[] $plugins loaded plugin instances
     */
    public function onPluginsLoaded(array $plugins)
    {
        $originalPlugins = $plugins;

        $this->triggerEvent('onPluginsLoaded', [ &$plugins ]);

        foreach ($plugins as $pluginName => $plugin) {
            if (!isset($originalPlugins[$pluginName])) {
                $this->getPico()->loadPlugin($plugin);
            } elseif ($plugin !== $originalPlugins[$pluginName]) {
                throw new \RuntimeException(
                    "A Pico plugin using API version 1 tried to replace Pico plugin '" . $pluginName . "' using the "
                    . "onPluginsLoaded() event, however, replacing plugins was removed with API version 2"
                );
            }

            unset($originalPlugins[$pluginName]);
        }

        if ($originalPlugins) {
            $removedPluginsList = implode("', '", array_keys($originalPlugins));
            throw new \RuntimeException(
                "A Pico plugin using API version 1 tried to unload the Pico plugin(s) '" . $removedPluginsList . "' "
                . "using the onPluginsLoaded() event, however, unloading plugins was removed with API version 2"
            );
        }
    }

    /**
     * Sets PluginApi1Plugin::$requestFile
     *
     * @see PluginApi1Plugin::$requestFile
     *
     * @param string &$file absolute path to the content file to serve
     */
    public function onRequestFile(&$file)
    {
        $this->requestFile = &$file;
    }

    /**
     * Triggers the onContentLoading event
     */
    public function onContentLoading()
    {
        $this->triggerEvent('onContentLoading', [ &$this->requestFile ]);
    }

    /**
     * Sets PluginApi1Plugin::$rawContent
     *
     * @see PluginApi1Plugin::$rawContent
     *
     * @param string &$rawContent raw file contents
     */
    public function onContentLoaded(&$rawContent)
    {
        $this->rawContent = &$rawContent;
    }

    /**
     * Triggers the on404ContentLoading event
     */
    public function on404ContentLoading()
    {
        $this->triggerEvent('on404ContentLoading', [ &$this->requestFile ]);
    }

    /**
     * Triggers the onMetaParsing event
     *
     * @see PluginApi1Plugin::onMetaHeaders()
     */
    public function onMetaParsing()
    {
        $headersFlipped = $this->getFlippedMetaHeaders();
        $this->triggerEvent('onMetaParsing', [ &$this->rawContent, &$headersFlipped ]);
        $this->updateFlippedMetaHeaders($headersFlipped);
    }

    /**
     * Triggers the onMetaParsed and onParsedownRegistration events
     *
     * @param string[] &$meta parsed meta data
     */
    public function onMetaParsed(array &$meta)
    {
        $this->triggerEvent('onMetaParsed', [ &$meta ]);
        $this->triggerEvent('onParsedownRegistration');
    }

    /**
     * Triggers the onContentParsing event
     */
    public function onContentParsing()
    {
        $this->triggerEvent('onContentParsing', [ &$this->rawContent ]);
    }

    /**
     * Sets PluginApi1Plugin::$pages
     *
     * @see PluginApi1Plugin::$pages
     *
     * @param array[] &$pages sorted list of all known pages
     */
    public function onPagesLoaded(array &$pages)
    {
        $this->pages = &$pages;
    }

    /**
     * Triggers the onPagesLoaded and onTwigRegistration events
     *
     * @param array|null &$currentPage  data of the page being served
     * @param array|null &$previousPage data of the previous page
     * @param array|null &$nextPage     data of the next page
     */
    public function onCurrentPageDiscovered(
        array &$currentPage = null,
        array &$previousPage = null,
        array &$nextPage = null
    ) {
        $this->triggerEvent('onPagesLoaded', [ &$this->pages, &$currentPage, &$previousPage, &$nextPage ]);

        $this->triggerEvent('onTwigRegistration');
        $this->getPico()->getTwig();
    }

    /**
     * Triggers the onPageRendering event
     *
     * @param string &$templateName  file name of the template
     * @param array  &$twigVariables template variables
     */
    public function onPageRendering(&$templateName, array &$twigVariables)
    {
        $this->triggerEvent('onPageRendering', [ &$this->twig, &$twigVariables, &$templateName ]);
    }

    /**
     * Triggers the onMetaHeaders event with flipped meta headers and sets
     * PluginApi1Plugin::$metaHeaders
     *
     * @see PluginApi1Plugin::$metaHeaders
     *
     * @param string[] &$headers list of known meta header fields; the array
     *     key specifies the YAML key to search for, the array value is later
     *     used to access the found value
     */
    public function onMetaHeaders(array &$headers)
    {
        $this->metaHeaders = &$headers;

        $headersFlipped = $this->getFlippedMetaHeaders();
        $this->triggerEvent('onMetaHeaders', [ &$headersFlipped ]);
        $this->updateFlippedMetaHeaders($headersFlipped);
    }

    /**
     * Sets PluginApi1Plugin::$twig
     *
     * @see PluginApi1Plugin::$twig
     *
     * @param TwigEnvironment &$twig Twig instance
     */
    public function onTwigRegistered(TwigEnvironment &$twig)
    {
        $this->twig = $twig;
    }

    /**
     * Returns the flipped meta headers array
     *
     * Pico 1.0 and earlier were using the values of the meta headers array to
     * match registered meta headers in a page's meta data, and used the keys
     * of the meta headers array to store the meta value in the page's meta
     * data. However, starting with Pico 2.0 it is the other way round. This
     * allows us to specify multiple "search strings" for a single registered
     * meta value (e.g. "Nyan Cat" and "Tac Nayn" can be synonmous).
     *
     * @return array flipped meta headers
     */
    protected function getFlippedMetaHeaders()
    {
        if ($this->metaHeaders === null) {
            // make sure to trigger the onMetaHeaders event
            $this->getPico()->getMetaHeaders();
        }

        return array_flip($this->metaHeaders ?: []);
    }

    /**
     * Syncs PluginApi1Plugin::$metaHeaders with a flipped headers array
     *
     * @param array $headersFlipped flipped headers array
     */
    protected function updateFlippedMetaHeaders(array $headersFlipped)
    {
        foreach ($this->metaHeaders as $name => $key) {
            if (!isset($headersFlipped[$key])) {
                unset($this->metaHeaders[$name]);
            }
        }

        foreach ($headersFlipped as $key => $name) {
            $this->metaHeaders[$name] = $key;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getApiVersion()
    {
        return PicoDeprecated::API_VERSION_2;
    }

    /**
     * {@inheritDoc}
     */
    public function getApiVersionSupport()
    {
        return PicoDeprecated::API_VERSION_1;
    }
}
