<?php
/**
 * This file is part of Pico. It's copyrighted by the contributors recorded
 * in the version control history of the file, available from the following
 * original location:
 *
 * <https://github.com/picocms/pico-deprecated/blob/master/lib/PluginApiPluginInterface.php>
 *
 * SPDX-License-Identifier: MIT
 * License-Filename: LICENSE
 */

namespace picocms\PicoDeprecated;

/**
 * Common interface for PicoDeprecated plugin API compatibility plugins
 *
 * @author  Daniel Rudolf
 * @link    https://picocms.org
 * @license https://opensource.org/licenses/MIT The MIT License
 * @version 3.0
 */
interface PluginApiPluginInterface extends PluginInterface
{
    /**
     * Handles custom events for plugins of the supported API version
     *
     * @param string $eventName name of the triggered event
     * @param array  $params    passed parameters
     */
    public function handleCustomEvent($eventName, array $params = []);

    /**
     * Returns the API version this plugin maintains backward compatibility for
     *
     * @return int
     */
    public function getApiVersionSupport();
}
