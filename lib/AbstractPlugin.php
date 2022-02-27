<?php
/**
 * This file is part of Pico. It's copyrighted by the contributors recorded
 * in the version control history of the file, available from the following
 * original location:
 *
 * <https://github.com/picocms/pico-deprecated/blob/master/lib/AbstractPlugin.php>
 *
 * SPDX-License-Identifier: MIT
 * License-Filename: LICENSE
 */

namespace picocms\PicoDeprecated;

use Pico;
use PicoDeprecated;

/**
 * Abstract class to extend from when implementing a PicoDeprecated
 * compatibility plugin
 *
 * Please refer to {@see PicoPluginInterface} for more information about how to
 * develop a PicoDeprecated compatibility plugin.
 *
 * @see PicoPluginInterface
 *
 * @author  Daniel Rudolf
 * @link    https://picocms.org
 * @license https://opensource.org/licenses/MIT The MIT License
 * @version 3.0
 */
abstract class AbstractPlugin implements PluginInterface
{
    /**
     * Current instance of Pico
     *
     * @see PicoPluginInterface::getPico()
     *
     * @var Pico
     */
    protected $pico;

    /**
     * Instance of the main PicoDeprecated plugin
     *
     * @see PluginInterface::getPicoDeprecated()
     *
     * @var PicoDeprecated
     */
    protected $picoDeprecated;

    /**
     * List of plugins which this plugin depends on
     *
     * @see PicoPluginInterface::getDependencies()
     *
     * @var string[]
     */
    protected $dependsOn = [];

    /**
     * Constructs a new instance of a PicoDeprecated compatibility plugin
     *
     * @param Pico           $pico           current instance of Pico
     * @param PicoDeprecated $picoDeprecated current instance of PicoDeprecated
     */
    public function __construct(Pico $pico, PicoDeprecated $picoDeprecated)
    {
        $this->pico = $pico;
        $this->picoDeprecated = $picoDeprecated;
    }

    /**
     * {@inheritDoc}
     */
    public function handleEvent($eventName, array $params)
    {
        if (method_exists($this, $eventName)) {
            call_user_func_array([ $this, $eventName ], $params);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPico()
    {
        return $this->pico;
    }

    /**
     * {@inheritDoc}
     */
    public function getPicoDeprecated()
    {
        return $this->picoDeprecated;
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return (array) $this->dependsOn;
    }
}
