<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Integration;

use Piwik\Db;
use Piwik\Translate;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group Core
 */
class TravisEnvironmentTest extends IntegrationTestCase
{
    public function testUsageOfCorrectMysqlAdapter()
    {
        $mysqlAdapter = getenv('MYSQL_ADAPTER');

        if (empty($mysqlAdapter)) {
            return;
        }

        $this->assertTrue(in_array($mysqlAdapter, array('PDO_MYSQL', 'PDO\MYSQL', 'MYSQLI')));

        $db = Db::get();

        switch ($mysqlAdapter) {
            case 'PDO_MYSQL':
            case 'PDO\MYSQL':
                $this->assertInstanceOf('Piwik\Db\Adapter\Pdo\Mysql', $db);
                break;
            case 'MYSQLI':
                $this->assertInstanceOf('Piwik\Db\Adapter\Mysqli', $db);
                break;
        }
    }
}
