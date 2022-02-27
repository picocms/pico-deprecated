<?php
/**
 * This file is part of Pico. It's copyrighted by the contributors recorded
 * in the version control history of the file, available from the following
 * original location:
 *
 * <https://github.com/picocms/pico-deprecated/blob/master/plugins/PluginApi2Plugin.php>
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

/**
 * Maintains backward compatibility with plugins using API version 2, written
 * for Pico 2.0
 *
 * @author  Daniel Rudolf
 * @link    https://picocms.org
 * @license https://opensource.org/licenses/MIT The MIT License
 * @version 3.0
 */
class PluginApi2Plugin extends AbstractPluginApiPlugin
{
    /**
     * This plugin extends {@see ThemeApi2Plugin}
     *
     * @var string[]
     */
    protected $dependsOn = [ ThemeApi2Plugin::class ];

    /**
     * Map of core events matching event signatures of older API versions
     *
     * @see AbstractPluginApiPlugin::handleEvent()
     *
     * @var array<string,string>
     */
    protected $eventAliases = [
        'onPluginsLoaded' =>         [ 'onPluginsLoaded' ],
        'onPluginManuallyLoaded' =>  [ 'onPluginManuallyLoaded' ],
        'onRequestUrl' =>            [ 'onRequestUrl' ],
        'onRequestFile' =>           [ 'onRequestFile' ],
        'onContentLoading' =>        [ 'onContentLoading' ],
        'on404ContentLoading' =>     [ 'on404ContentLoading' ],
        'on404ContentLoaded' =>      [ 'on404ContentLoaded' ],
        'onContentLoaded' =>         [ 'onContentLoaded' ],
        'onMetaParsing' =>           [ 'onMetaParsing' ],
        'onMetaParsed' =>            [ 'onMetaParsed' ],
        'onContentParsing' =>        [ 'onContentParsing' ],
        'onContentPrepared' =>       [ 'onContentPrepared' ],
        'onContentParsed' =>         [ 'onContentParsed' ],
        'onPagesLoading' =>          [ 'onPagesLoading' ],
        'onSinglePageLoading' =>     [ 'onSinglePageLoading' ],
        'onSinglePageContent' =>     [ 'onSinglePageContent' ],
        'onSinglePageLoaded' =>      [ 'onSinglePageLoaded' ],
        'onPagesDiscovered' =>       [ 'onPagesDiscovered' ],
        'onPagesLoaded' =>           [ 'onPagesLoaded' ],
        'onCurrentPageDiscovered' => [ 'onCurrentPageDiscovered' ],
        'onPageTreeBuilt' =>         [ 'onPageTreeBuilt' ],
        'onPageRendering' =>         [ 'onPageRendering' ],
        'onPageRendered' =>          [ 'onPageRendered' ],
        'onMetaHeaders' =>           [ 'onMetaHeaders' ],
        'onYamlParserRegistered' =>  [ 'onYamlParserRegistered' ],
        'onParsedownRegistered' =>   [ 'onParsedownRegistered' ],
        'onTwigRegistered' =>        [ 'onTwigRegistered' ],
    ];

    /**
     * Pico's config array
     *
     * @see Pico::$config
     * @see PluginApi2Plugin::onConfigLoaded()
     *
     * @var array|null
     */
    protected $config;

    /**
     * Sets PluginApi2Plugin::$config and handles the theme_url config param
     *
     * @see PluginApi2Plugin::$config
     *
     * @param array $config
     */
    public function onConfigLoaded(array &$config)
    {
        $this->config = &$config;

        if (!empty($config['theme_url'])) {
            $config['themes_url'] = $this->getPico()->getAbsoluteUrl($config['theme_url']);
            $config['theme_url'] = &$config['themes_url'];
        }
    }

    /**
     * Triggers the onConfigLoaded event
     *
     * @param string $theme           name of current theme
     * @param int    $themeApiVersion API version of the theme
     * @param array  &$themeConfig    config array of the theme
     */
    public function onThemeLoaded($theme, $themeApiVersion, array &$themeConfig)
    {
        $this->triggerEvent('onConfigLoaded', [ &$this->config ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getApiVersion()
    {
        return PicoDeprecated::API_VERSION_3;
    }

    /**
     * {@inheritDoc}
     */
    public function getApiVersionSupport()
    {
        return PicoDeprecated::API_VERSION_2;
    }
}
