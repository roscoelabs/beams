<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Updates;

use Piwik\Updates;

/**
 */
class Updates_1_12_b15 extends Updates
{
    public static function update()
    {
        try {
            \Piwik\Plugin\Manager::getInstance()->activatePlugin('SegmentEditor');
        } catch (\Exception $e) {
            // pass
        }
    }
}
