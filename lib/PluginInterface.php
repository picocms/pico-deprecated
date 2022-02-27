<?php
/**
 * This file is part of Pico. It's copyrighted by the contributors recorded
 * in the version control history of the file, available from the following
 * original location:
 *
 * <https://github.com/picocms/pico-deprecated/blob/master/lib/PluginInterface.php>
 *
 * SPDX-License-Identifier: MIT
 * License-Filename: LICENSE
 */

namespace picocms\PicoDeprecated;

use Pico;
use PicoDeprecated;

/**
 * Common interface for PicoDeprecated compatibility plugins
 *
 * @author  Daniel Rudolf
 * @link    https://picocms.org
 * @license https://opensource.org/licenses/MIT The MIT License
 * @version 3.0
 */
interface PluginInterface
{
    /**
     * Handles a Pico event
     *
     * @param string $eventName name of the triggered event
     * @param array  $params    passed parameters
     */
    public function handleEvent($eventName, array $params);

    /**
     * Returns a list of names of compat plugins required by this plugin
     *
     * @return string[] required plugins
     */
    public function getDependencies();

    /**
     * Returns the plugin's instance of Pico
     *
     * @see Pico
     *
     * @return Pico the plugin's instance of Pico
     */
    public function getPico();

    /**
     * Returns the plugin's main PicoDeprecated plugin instance
     *
     * @see PicoDeprecated
     *
     * @return PicoDeprecated the plugin's instance of Pico
     */
    public function getPicoDeprecated();

    /**
     * Returns the version of the API this plugin uses
     *
     * @return int the API version used by this plugin
     */
    public function getApiVersion();
}
