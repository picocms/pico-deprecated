<?php
/**
 * This file is part of Pico. It's copyrighted by the contributors recorded
 * in the version control history of the file, available from the following
 * original location:
 *
 * <https://github.com/picocms/pico-deprecated/blob/master/plugins/PluginApi3Plugin.php>
 *
 * SPDX-License-Identifier: MIT
 * License-Filename: LICENSE
 */

namespace picocms\PicoDeprecated\Plugin;

use picocms\PicoDeprecated\AbstractPluginApiPlugin;
use PicoDeprecated;

/**
 * Maintains backward compatibility with plugins using API version 3, written
 * for Pico 2.1
 *
 * @author  Daniel Rudolf
 * @link    https://picocms.org
 * @license https://opensource.org/licenses/MIT The MIT License
 * @version 3.0
 */
class PluginApi3Plugin extends AbstractPluginApiPlugin
{
    /**
     * This plugin extends {@see ThemeApi3Plugin}
     *
     * @var string[]
     */
    protected $dependsOn = [ ThemeApi3Plugin::class ];

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
        'onConfigLoaded' =>          [ 'onConfigLoaded' ],
        'onThemeLoading' =>          [ 'onThemeLoading' ],
        'onThemeLoaded' =>           [ 'onThemeLoaded' ],
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
     * {@inheritDoc}
     */
    public function getApiVersion()
    {
        return PicoDeprecated::API_VERSION_4;
    }

    /**
     * {@inheritDoc}
     */
    public function getApiVersionSupport()
    {
        return PicoDeprecated::API_VERSION_3;
    }
}
