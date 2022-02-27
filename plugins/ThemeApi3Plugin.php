<?php
/**
 * This file is part of Pico. It's copyrighted by the contributors recorded
 * in the version control history of the file, available from the following
 * original location:
 *
 * <https://github.com/picocms/pico-deprecated/blob/master/plugins/ThemeApi3Plugin.php>
 *
 * SPDX-License-Identifier: MIT
 * License-Filename: LICENSE
 */

namespace picocms\PicoDeprecated\Plugin;

use picocms\PicoDeprecated\AbstractPlugin;
use PicoDeprecated;

/**
 * Maintains backward compatibility with themes using API version 3, written
 * for Pico 2.1
 *
 * @author  Daniel Rudolf
 * @link    https://picocms.org
 * @license https://opensource.org/licenses/MIT The MIT License
 * @version 3.0
 */
class ThemeApi3Plugin extends AbstractPlugin
{
    /**
     * {@inheritDoc}
     */
    public function getApiVersion()
    {
        return PicoDeprecated::API_VERSION_4;
    }
}
