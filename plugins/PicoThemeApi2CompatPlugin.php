<?php
/**
 * This file is part of Pico. It's copyrighted by the contributors recorded
 * in the version control history of the file, available from the following
 * original location:
 *
 * <https://github.com/picocms/pico-deprecated/blob/master/plugins/PicoThemeApi2CompatPlugin.php>
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

/**
 * Maintains backward compatibility with themes using API version 2, written
 * for Pico 2.0
 *
 * @author  Daniel Rudolf
 * @link    http://picocms.org
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 2.1
 */
class PicoThemeApi2CompatPlugin extends AbstractPicoCompatPlugin
{
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
     * {@inheritDoc}
     */
    public function getApiVersion()
    {
        return PicoDeprecated::API_VERSION_3;
    }
}
