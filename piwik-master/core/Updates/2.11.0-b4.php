<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Updates;

use Piwik\DataAccess\ArchiveTableCreator;
use Piwik\Updater;
use Piwik\Updates;

class Updates_2_11_0_b4 extends Updates
{

    public static function getSql()
    {
        $sqls = array();

        $archiveTables = ArchiveTableCreator::getTablesArchivesInstalled();

        $archiveBlobTables = array_filter($archiveTables, function ($name) {
            return ArchiveTableCreator::getTypeFromTableName($name) == ArchiveTableCreator::BLOB_TABLE;
        });

        foreach ($archiveBlobTables as $table) {
            $sqls["UPDATE " . $table . " SET name = 'UserLanguage_language' WHERE name = 'UserSettings_language'"] = false;
        }

        return $sqls;
    }

    public static function update()
    {
        $pluginManager = \Piwik\Plugin\Manager::getInstance();

        try {
            $pluginManager->activatePlugin('UserLanguage');
        } catch (\Exception $e) {
        }

        Updater::updateDatabase(__FILE__, self::getSql());
    }
}
