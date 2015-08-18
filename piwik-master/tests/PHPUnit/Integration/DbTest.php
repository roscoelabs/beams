<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Integration;

use Piwik\Common;
use Piwik\Db;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group Core
 */
class DbTest extends IntegrationTestCase
{
    public function test_getColumnNamesFromTable()
    {
        $this->assertColumnNames('access', array('login', 'idsite', 'access'));
        $this->assertColumnNames('option', array('option_name', 'option_value', 'autoload'));
    }

    private function assertColumnNames($tableName, $expectedColumnNames)
    {
        $colmuns = Db::getColumnNamesFromTable(Common::prefixTable($tableName));

        $this->assertEquals($expectedColumnNames, $colmuns);
    }

    /**
     * @dataProvider getIsOptimizeInnoDBTestData
     */
    public function test_isOptimizeInnoDBSupported_ReturnsCorrectResult($version, $expectedResult)
    {
        $result = Db::isOptimizeInnoDBSupported($version);
        $this->assertEquals($expectedResult, $result);
    }

    public function getIsOptimizeInnoDBTestData()
    {
        return array(
            array("10.0.17-MariaDB-1~trusty", false),
            array("10.1.1-MariaDB-1~trusty", true),
            array("10.2.0-MariaDB-1~trusty", true),
            array("10.6.19-0ubuntu0.14.04.1", false),

            // for sanity. maybe not ours.
            array("", false),
            array(0, false),
            array(false, false),
            array("slkdf(@*#lkesjfMariaDB", false),
            array("slkdfjq3rujlkv", false),
        );
    }
}
