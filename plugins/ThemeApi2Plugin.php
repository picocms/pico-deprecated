<?php
/**
 * This file is part of Pico. It's copyrighted by the contributors recorded
 * in the version control history of the file, available from the following
 * original location:
 *
 * <https://github.com/picocms/pico-deprecated/blob/master/plugins/ThemeApi2Plugin.php>
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

use picocms\PicoDeprecated\AbstractPlugin;
use PicoDeprecated;
use Twig\Environment as TwigEnvironment;
use Twig\Error\LoaderError as TwigLoaderError;
use Twig\Extension\EscaperExtension as TwigEscaperExtension;
use Twig\Loader\LoaderInterface as TwigLoaderInterface;

/**
 * Maintains backward compatibility with themes using API version 2, written
 * for Pico 2.0
 *
 * @author  Daniel Rudolf
 * @link    https://picocms.org
 * @license https://opensource.org/licenses/MIT The MIT License
 * @version 3.0
 */
class ThemeApi2Plugin extends AbstractPlugin
{
    /**
     * Manually configured Twig escape strategy
     *
     * @var mixed|null
     */
    protected $twigEscapeStrategy;

    /**
     * Directory paths of plugins
     *
     * @var string[]
     */
    protected $pluginPaths = [];

    /**
     * Sets ThemeApi2Plugin::$twigEscapeStrategy
     *
     * @see ThemeApi2Plugin::$twigEscapeStrategy
     *
     * @param array &$config array of config variables
     */
    public function onConfigLoaded(array &$config)
    {
        if (isset($config['twig_config']['autoescape'])) {
            $this->twigEscapeStrategy = $config['twig_config']['autoescape'];
        }
    }

    /**
     * Re-introduces the Twig variables prev_page, base_dir and theme_dir
     *
     * @param string &$templateName  file name of the template
     * @param array  &$twigVariables template variables
     */
    public function onPageRendering(&$templateName, array &$twigVariables)
    {
        $twigVariables['prev_page'] = &$twigVariables['previous_page'];
        $twigVariables['base_dir'] = rtrim($this->getPico()->getRootDir(), '/');
        $twigVariables['theme_dir'] = $this->getPico()->getThemesDir() . $this->getPico()->getTheme();
    }

    /**
     * Registers PluginApi2Plugin::twigEscapeStrategy() as Twig's default
     * escape strategy
     *
     * @see PluginApi2Plugin::twigEscapeStrategy()
     *
     * @param TwigEnvironment &$twig Twig instance
     */
    public function onTwigRegistered(TwigEnvironment &$twig)
    {
        if ($twig->hasExtension(TwigEscaperExtension::class)) {
            /** @var TwigEscaperExtension $escaperExtension */
            $escaperExtension = $twig->getExtension(TwigEscaperExtension::class);
            $escaperExtension->setDefaultStrategy([ $this, 'twigEscapeStrategy' ]);
        }
    }

    /**
     * Returns Twig's default escaping strategy for the given template
     *
     * This escape strategy takes a template name and decides whether Twig's
     * global default escape strategy should be used, or escaping should be
     * disabled. Escaping is disabled for themes using API v2 and below as well
     * as for templates of plugins using API v2 and below. If a escape strategy
     * has been configured manually, this method always returns this explicitly
     * configured escape strategy.
     *
     * @param string $templateName template name
     *
     * @return string|false escape strategy for this template
     */
    public function twigEscapeStrategy($templateName)
    {
        $twigConfig = $this->getPico()->getConfig('twig_config');
        $escapeStrategy = $twigConfig['autoescape'];

        if (($this->twigEscapeStrategy !== null) && ($escapeStrategy === $this->twigEscapeStrategy)) {
            return $escapeStrategy;
        }

        if (!is_string($escapeStrategy) && ($escapeStrategy !== false)) {
            $escapeStrategy = call_user_func($escapeStrategy, $templateName);
        }

        if ($escapeStrategy === false) {
            return false;
        }

        /** @var TwigLoaderInterface $twigLoader */
        $twigLoader = $this->getPico()->getTwig()->getLoader();

        try {
            $templatePath = $twigLoader->getSourceContext($templateName)->getPath();
        } catch (TwigLoaderError $e) {
            $templatePath = '';
        }

        if ($templatePath) {
            $themePath = realpath($this->getPico()->getThemesDir() . $this->getPico()->getTheme()) . '/';
            if (substr_compare($templatePath, $themePath, 0, strlen($themePath)) === 0) {
                $themeApiVersion = $this->getPico()->getThemeApiVersion();
                return ($themeApiVersion >= PicoDeprecated::API_VERSION_3) ? $escapeStrategy : false;
            }

            $plugin = $this->getPluginFromPath($templatePath);
            if ($plugin) {
                $pluginApiVersion = $this->getPicoDeprecated()->getPluginApiVersion($plugin);
                return ($pluginApiVersion >= PicoDeprecated::API_VERSION_3) ? $escapeStrategy : false;
            }
        }

        // unknown template path
        // to preserve BC we must assume that the template uses an old API version
        return false;
    }

    /**
     * Returns the matching plugin instance when the given path is within a
     * plugin's base directory
     *
     * @param string $path file path to search for
     *
     * @return object|null either the matching plugin instance or NULL
     */
    protected function getPluginFromPath($path)
    {
        $plugins = $this->getPico()->getPlugins();
        foreach ($this->pluginPaths as $pluginName => $pluginPath) {
            if ($pluginPath && (substr_compare($path, $pluginPath, 0, strlen($pluginPath)) === 0)) {
                return $plugins[$pluginName];
            }
        }

        $rootDir = realpath($this->getPico()->getRootDir()) . '/';
        $vendorDir = realpath($this->getPico()->getVendorDir()) . '/';
        $pluginsDir = realpath($this->getPico()->getPluginsDir()) . '/';
        $themesDir = realpath($this->getPico()->getThemesDir()) . '/';
        foreach ($plugins as $pluginName => $plugin) {
            if (isset($this->pluginPaths[$pluginName])) {
                continue;
            }

            $pluginReflector = new \ReflectionObject($plugin);

            $pluginPath = dirname($pluginReflector->getFileName() ?: '') . '/';
            if (in_array($pluginPath, [ '/', $rootDir, $vendorDir, $pluginsDir, $themesDir ], true)) {
                $pluginPath = '';
            }

            $this->pluginPaths[$pluginName] = $pluginPath;

            if ($pluginPath && (substr_compare($path, $pluginPath, 0, strlen($pluginPath)) === 0)) {
                return $plugins[$pluginName];
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getApiVersion()
    {
        return PicoDeprecated::API_VERSION_3;
    }
}
