<?php
/**
 * This file is part of Pico. It's copyrighted by the contributors recorded
 * in the version control history of the file, available from the following
 * original location:
 *
 * <https://github.com/picocms/pico-deprecated/blob/master/plugins/ThemeApi0Plugin.php>
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

declare(strict_types=1);

namespace picocms\PicoDeprecated\Plugin;

use picocms\PicoDeprecated\AbstractPlugin;
use PicoDeprecated;

/**
 * Maintains backward compatibility with themes using API version 0, written
 * for Pico 0.9 and earlier
 *
 * Since there were no theme-related changes between Pico 0.9 and Pico 1.0,
 * this compat plugin doesn't hold any code itself, it just depends on
 * {@see ThemeApi1Plugin}. Since themes didn't support API versioning until
 * Pico 2.1 (i.e. API version 3), all older themes will appear to use API
 * version 0.
 *
 * @author  Daniel Rudolf
 * @link    https://picocms.org
 * @license https://opensource.org/licenses/MIT The MIT License
 * @version 3.0
 */
class ThemeApi0Plugin extends AbstractPlugin
{
    /**
     * This plugin extends {@see ThemeApi1Plugin}
     *
     * @var string[]
     */
    protected $dependsOn = [ ThemeApi1Plugin::class ];

    /**
     * {@inheritDoc}
     */
    public function getApiVersion(): int
    {
        return PicoDeprecated::API_VERSION_3;
    }
}
