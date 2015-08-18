<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Updates;

use Piwik\Common;
use Piwik\Updater;
use Piwik\Updates;

class Updates_2_4_0_b8 extends Updates
{
    public static function getSql()
    {
        return array(
            "ALTER TABLE `" . Common::prefixTable('session')
            . "` CHANGE `id` `id` VARCHAR( 255 ) NOT NULL " => false,
        );
    }

    public static function update()
    {
        Updater::updateDatabase(__FILE__, self::getSql());
    }
}
