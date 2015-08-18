<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Updates;

use Piwik\Filesystem;
use Piwik\Tracker\Cache;
use Piwik\Updates;

/**
 * Update for version 2.5.0-rc4.
 */
class Updates_2_5_0_rc4 extends Updates
{

    public static function update()
    {
        Cache::deleteTrackerCache();
        Filesystem::clearPhpCaches();
    }
}
