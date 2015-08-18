<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Integration;

use Exception;
use Piwik\Access;
use Piwik\ArchiveProcessor;
use Piwik\ArchiveProcessor\Rules;
use Piwik\Common;
use Piwik\DataAccess\ArchiveTableCreator;
use Piwik\Date;
use Piwik\Db;
use Piwik\Db\BatchInsert;
use Piwik\Period;
use Piwik\Piwik;
use Piwik\Plugins\SitesManager\API;
use Piwik\Segment;
use Piwik\SettingsServer;
use Piwik\Site;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

class ArchiveProcessorTest extends ArchiveProcessor\Loader
{
    public function getParams()
    {
        return $this->params;
    }

    public function public_getMinTimeArchiveProcessed()
    {
        return $this->getMinTimeArchiveProcessed();
    }

    public function public_isArchiveTemporary()
    {
        return $this->isArchiveTemporary();
    }
}

/**
 * @group Core
 */
class ArchiveProcessingTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();

        // setup the access layer
        FakeAccess::$superUser = true;

        ArchiveTableCreator::$tablesAlreadyInstalled = null;
    }

    public function tearDown()
    {
        ArchiveTableCreator::$tablesAlreadyInstalled = null;
    }

    /**
     * Creates a new website
     *
     * @param string $timezone
     * @return Site
     */
    private function _createWebsite($timezone = 'UTC')
    {
        $idSite = API::getInstance()->addSite(
            "site1",
            array("http://piwik.net"),
            $ecommerce = 0,
            $siteSearch = 1, $searchKeywordParameters = null, $searchCategoryParameters = null,
            $excludedIps = "",
            $excludedQueryParameters = "",
            $timezone);

        Site::clearCache();
        return new Site($idSite);
    }

    /**
     * Creates a new ArchiveProcessor object
     *
     * @param string $periodLabel
     * @param string $dateLabel
     * @param string $siteTimezone
     * @return ArchiveProcessorTest
     */
    private function _createArchiveProcessor($periodLabel, $dateLabel, $siteTimezone)
    {
        $site = $this->_createWebsite($siteTimezone);
        $date = Date::factory($dateLabel);
        $period = Period\Factory::build($periodLabel, $date);
        $segment = new Segment('', $site->getId());

        $params = new ArchiveProcessor\Parameters($site, $period, $segment);
        return new ArchiveProcessorTest($params);
    }

    /**
     * test of validity of an archive, for a month not finished
     */
    public function testInitCurrentMonth()
    {
        $siteTimezone = 'UTC+10';
        $now = time();
        // this test fails in the last 10 hours of the last day of the month
        if(date('m', $now) != date('m', $now + 10 * 3600)) {
            $this->markTestSkipped('testInitCurrentMonth will fail in the last hours of the month, skipping...');
        }

        $dateLabel = date('Y-m-d', $now);
        $archiveProcessor = $this->_createArchiveProcessor('month', $dateLabel, $siteTimezone);
        $archiveProcessor->time = $now;

        // min finished timestamp considered when looking at archive timestamp
        $timeout = Rules::getTodayArchiveTimeToLive();
        $this->assertTrue($timeout >= 10);
        $dateMinArchived = $now - $timeout;
        $this->compareTimestamps($dateMinArchived, $archiveProcessor->public_getMinTimeArchiveProcessed());

        $this->assertTrue($archiveProcessor->public_isArchiveTemporary());
    }

    private function compareTimestamps($expected, $processed)
    {
//        $messageIfFails = Date::factory($expected)->getDatetime() . " != " . Date::factory($processed)->getDatetime();
        $messageIfFails = "Expected [$expected] but got [$processed]";
        $this->assertTrue( abs($expected-$processed) <=4 , $messageIfFails);
    }

    /**
     * test of validity of an archive, for a month in the past
     */
    public function testInitDayInPast()
    {
        $archiveProcessor = $this->_createArchiveProcessor('day', '2010-01-01', 'UTC');

        // min finished timestamp considered when looking at archive timestamp
        $dateMinArchived = Date::factory('2010-01-02')->getTimestamp();
        $this->assertEquals($dateMinArchived, $archiveProcessor->public_getMinTimeArchiveProcessed() + 1);

        $this->assertEquals('2010-01-01 00:00:00', $archiveProcessor->getParams()->getDateStart()->getDateStartUTC());
        $this->assertEquals('2010-01-01 23:59:59', $archiveProcessor->getParams()->getDateEnd()->getDateEndUTC());
        $this->assertFalse($archiveProcessor->public_isArchiveTemporary());
    }

    /**
     * test of validity of an archive, for a non UTC date in the past
     */
    public function testInitDayInPastNonUTCWebsite()
    {
        $timezone = 'UTC+5.5';
        $archiveProcessor = $this->_createArchiveProcessor('day', '2010-01-01', $timezone);
        // min finished timestamp considered when looking at archive timestamp
        $dateMinArchived = Date::factory('2010-01-01 18:30:00');
        $this->assertEquals($dateMinArchived->getTimestamp(), $archiveProcessor->public_getMinTimeArchiveProcessed() + 1);

        $this->assertEquals('2009-12-31 18:30:00', $archiveProcessor->getParams()->getDateStart()->getDateStartUTC());
        $this->assertEquals('2010-01-01 18:29:59', $archiveProcessor->getParams()->getDateEnd()->getDateEndUTC());
        $this->assertFalse($archiveProcessor->public_isArchiveTemporary());
    }

    /**
     * test of validity of an archive, for a non UTC month in the past
     */
    public function testInitMonthInPastNonUTCWebsite()
    {
        $timezone = 'UTC-5.5';
        $archiveProcessor = $this->_createArchiveProcessor('month', '2010-01-02', $timezone);
        // min finished timestamp considered when looking at archive timestamp
        $dateMinArchived = Date::factory('2010-02-01 05:30:00');
        $this->assertEquals($dateMinArchived->getTimestamp(), $archiveProcessor->public_getMinTimeArchiveProcessed() + 1);

        $this->assertEquals('2010-01-01 05:30:00', $archiveProcessor->getParams()->getDateStart()->getDateStartUTC());
        $this->assertEquals('2010-02-01 05:29:59', $archiveProcessor->getParams()->getDateEnd()->getDateEndUTC());
        $this->assertFalse($archiveProcessor->public_isArchiveTemporary());
    }

    /**
     * test of validity of an archive, for today's archive
     */
    public function testInitToday()
    {
        $now = time();
        $siteTimezone = 'UTC-1';
        $timestamp = Date::factory('now', $siteTimezone)->getTimestamp();
        $dateLabel = date('Y-m-d', $timestamp);

        Rules::setBrowserTriggerArchiving(true);

        $archiveProcessor = $this->_createArchiveProcessor('day', $dateLabel, $siteTimezone);
        $archiveProcessor->time = $now;

        // we look at anything processed within the time to live range
        $dateMinArchived = $now - Rules::getTodayArchiveTimeToLive();
        $this->compareTimestamps($dateMinArchived, $archiveProcessor->public_getMinTimeArchiveProcessed() );
        $this->assertTrue($archiveProcessor->public_isArchiveTemporary());

        // when browsers don't trigger archives...
        Rules::setBrowserTriggerArchiving(false);
        // ...we force ArchiveProcessor to fetch any of the most recent archive
        $dateMinArchived = false;

        $this->compareTimestamps($dateMinArchived, $archiveProcessor->public_getMinTimeArchiveProcessed());

        $this->assertEquals(date('Y-m-d', $timestamp) . ' 01:00:00', $archiveProcessor->getParams()->getDateStart()->getDateStartUTC());
        $this->assertEquals(date('Y-m-d', $timestamp + 86400) . ' 00:59:59', $archiveProcessor->getParams()->getDateEnd()->getDateEndUTC());
        $this->assertTrue($archiveProcessor->public_isArchiveTemporary());
    }

    /**
     * test of validity of an archive, for today's archive with european timezone
     */
    public function testInitTodayEurope()
    {
        if (!SettingsServer::isTimezoneSupportEnabled()) {
            $this->markTestSkipped('timezones needs to be supported');
        }

        $now = time();
        $siteTimezone = 'Europe/Paris';
        $timestamp = Date::factory('now', $siteTimezone)->getTimestamp();
        $dateLabel = date('Y-m-d', $timestamp);

        Rules::setBrowserTriggerArchiving(true);

        $archiveProcessor = $this->_createArchiveProcessor('day', $dateLabel, $siteTimezone);
        $archiveProcessor->time = $now;

        // we look at anything processed within the time to live range
        $dateMinArchived = $now - Rules::getTodayArchiveTimeToLive();
        $this->compareTimestamps($dateMinArchived, $archiveProcessor->public_getMinTimeArchiveProcessed());
        $this->assertTrue($archiveProcessor->public_isArchiveTemporary());

        // when browsers don't trigger archives...
        Rules::setBrowserTriggerArchiving(false);
        // ...we force ArchiveProcessor to fetch any of the most recent archive
        $dateMinArchived = false;

        $this->compareTimestamps($dateMinArchived, $archiveProcessor->public_getMinTimeArchiveProcessed());

        // this test varies with DST
        $this->assertTrue($archiveProcessor->getParams()->getDateStart()->getDateStartUTC() == date('Y-m-d', $timestamp - 86400) . ' 22:00:00' ||
            $archiveProcessor->getParams()->getDateStart()->getDateStartUTC() == date('Y-m-d', $timestamp - 86400) . ' 23:00:00');
        $this->assertTrue($archiveProcessor->getParams()->getDateEnd()->getDateEndUTC() == date('Y-m-d', $timestamp) . ' 21:59:59' ||
            $archiveProcessor->getParams()->getDateEnd()->getDateEndUTC() == date('Y-m-d', $timestamp) . ' 22:59:59');

        $this->assertTrue($archiveProcessor->public_isArchiveTemporary());
    }

    /**
     * test of validity of an archive, for today's archive with toronto's timezone
     */
    public function testInitTodayToronto()
    {
        if (!SettingsServer::isTimezoneSupportEnabled()) {
            $this->markTestSkipped('timezones needs to be supported');
        }

        $now = time();
        $siteTimezone = 'America/Toronto';
        $timestamp = Date::factory('now', $siteTimezone)->getTimestamp();
        $dateLabel = date('Y-m-d', $timestamp);

        Rules::setBrowserTriggerArchiving(true);

        $archiveProcessor = $this->_createArchiveProcessor('day', $dateLabel, $siteTimezone);
        $archiveProcessor->time = $now;

        // we look at anything processed within the time to live range
        $dateMinArchived = $now - Rules::getTodayArchiveTimeToLive();
        $this->compareTimestamps($dateMinArchived, $archiveProcessor->public_getMinTimeArchiveProcessed() );
        $this->assertTrue($archiveProcessor->public_isArchiveTemporary());

        // when browsers don't trigger archives...
        Rules::setBrowserTriggerArchiving(false);
        // ...we force ArchiveProcessor to fetch any of the most recent archive
        $dateMinArchived = false;
        $this->compareTimestamps($dateMinArchived, $archiveProcessor->public_getMinTimeArchiveProcessed());

        // this test varies with DST
        $this->assertTrue($archiveProcessor->getParams()->getDateStart()->getDateStartUTC() == date('Y-m-d', $timestamp) . ' 04:00:00' ||
            $archiveProcessor->getParams()->getDateStart()->getDateStartUTC() == date('Y-m-d', $timestamp) . ' 05:00:00');
        $this->assertTrue($archiveProcessor->getParams()->getDateEnd()->getDateEndUTC() == date('Y-m-d', $timestamp + 86400) . ' 03:59:59' ||
            $archiveProcessor->getParams()->getDateEnd()->getDateEndUTC() == date('Y-m-d', $timestamp + 86400) . ' 04:59:59');

        $this->assertTrue($archiveProcessor->public_isArchiveTemporary());
    }

    /**
     * Testing batch insert
     */
    public function testTableInsertBatch()
    {
        $table = Common::prefixTable('site_url');
        $data = $this->_getDataInsert();
        try {
            $didWeUseBulk = BatchInsert::tableInsertBatch($table,
                array('idsite', 'url'),
                $data,
                $throwException = true);

        } catch (Exception $e) {
            $didWeUseBulk = $e->getMessage();
        }
        $this->_checkLoadDataInFileWasUsed($didWeUseBulk);

        if ($didWeUseBulk === true) {
            $this->_checkTableIsExpected($table, $data);

            // INSERT again the bulk. Because we use keyword LOCAL the data will be REPLACED automatically (see mysql doc)
            BatchInsert::tableInsertBatch($table, array('idsite', 'url'), $data);
            $this->_checkTableIsExpected($table, $data);
        }
    }

    protected function _checkLoadDataInFileWasUsed($didWeUseBulk)
    {
        static $skippedOnce = false;
        if ($didWeUseBulk !== true
            && $skippedOnce === false
        ) {
            $skippedOnce = true;
            $this->fail(
                'Performance notice: LOAD DATA [LOCAL] INFILE query is not working, so Piwik will fallback to using plain INSERTs '
                    . ' which will result in a slightly slower Archiving process.'
                    . ". \n"
                    . ' The error Messages from MySQL were: '
                    . $didWeUseBulk
                    . "\n\n Learn more how to enable LOAD LOCAL DATA INFILE see the Mysql doc (http://dev.mysql.com/doc/refman/5.0/en/load-data-local.html) "
                    . "\n   or ask in this Piwik ticket (https://github.com/piwik/piwik/issues/3605)"
            );
        }
        return $didWeUseBulk;
    }

    /**
     * Testing plain inserts
     */
    public function testTableInsertBatchIterate()
    {
        $table = Common::prefixTable('site_url');
        $data = $this->_getDataInsert();
        BatchInsert::tableInsertBatchIterate($table, array('idsite', 'url'), $data);
        $this->_checkTableIsExpected($table, $data);

        // If we insert AGAIN, expect to throw an error because the primary key already exists
        try {
            BatchInsert::tableInsertBatchIterate($table, array('idsite', 'url'), $data, $ignoreWhenDuplicate = false);
        } catch (Exception $e) {
            // However if we insert with keyword REPLACE, then the new data should be saved
            BatchInsert::tableInsertBatchIterate($table, array('idsite', 'url'), $data, $ignoreWhenDuplicate = true);
            $this->_checkTableIsExpected($table, $data);
            return;
        }
        $this->fail('Exception expected');
    }

    /**
     * Testing batch insert (BLOB)
     */
    public function testTableInsertBatchBlob()
    {
        $dateLabel = '2011-03-31';
        $table = ArchiveTableCreator::getBlobTable(Date::factory($dateLabel));

        $data = $this->_getBlobDataInsert();
        try {
            $didWeUseBulk = BatchInsert::tableInsertBatch($table,
                array('idarchive', 'name', 'idsite', 'date1', 'date2', 'period', 'ts_archived', 'value'),
                $data,
                $throwException = true);
        } catch (Exception $e) {
            $didWeUseBulk = $e->getMessage();
        }
        $this->_checkLoadDataInFileWasUsed($didWeUseBulk);

        // If bulk wasn't used the exception was caught and the INSERT didn't work
        if ($didWeUseBulk === true) {
            $this->_checkTableIsExpectedBlob($table, $data);
        }
        // INSERT again the bulk. Because we use keyword LOCAL the data will be REPLACED automatically (see mysql doc)
        $didWeUseBulk = BatchInsert::tableInsertBatch($table, array('idarchive', 'name', 'idsite', 'date1', 'date2', 'period', 'ts_archived', 'value'), $data);
        if ($didWeUseBulk === true) {
            $this->_checkTableIsExpectedBlob($table, $data);
        }
    }

    /**
     * Testing plain inserts (BLOB)
     */
    public function testTableInsertBatchIterateBlob()
    {
        $dateLabel = '2011-03-31';
        $table = ArchiveTableCreator::getBlobTable(Date::factory($dateLabel));

        $data = $this->_getBlobDataInsert();
        BatchInsert::tableInsertBatchIterate($table, array('idarchive', 'name', 'idsite', 'date1', 'date2', 'period', 'ts_archived', 'value'), $data);
        $this->_checkTableIsExpectedBlob($table, $data);

        // If we insert AGAIN, expect to throw an error because the primary key already exist
        try {
            BatchInsert::tableInsertBatchIterate($table, array('idarchive', 'name', 'idsite', 'date1', 'date2', 'period', 'ts_archived', 'value'), $data, $ignoreWhenDuplicate = false);
        } catch (Exception $e) {
            // However if we insert with keyword REPLACE, then the new data should be saved
            BatchInsert::tableInsertBatchIterate($table, array('idarchive', 'name', 'idsite', 'date1', 'date2', 'period', 'ts_archived', 'value'), $data, $ignoreWhenDuplicate = true);
            $this->_checkTableIsExpectedBlob($table, $data);
            return;
        }
        $this->fail('Exception expected');
    }

    protected function _checkTableIsExpected($table, $data)
    {
        $fetched = Db::fetchAll('SELECT * FROM ' . $table);
        foreach ($data as $id => $row) {
            $this->assertEquals($fetched[$id]['idsite'], $data[$id][0], "record $id is not {$data[$id][0]}");
            $this->assertEquals($fetched[$id]['url'], $data[$id][1], "Record $id bug, not {$data[$id][1]} BUT {$fetched[$id]['url']}");
        }
    }

    protected function _checkTableIsExpectedBlob($table, $data)
    {
        $fetched = Db::fetchAll('SELECT * FROM ' . $table);
        foreach ($data as $id => $row) {
            $this->assertEquals($fetched[$id]['idarchive'], $data[$id][0], "record $id idarchive is not '{$data[$id][0]}'");
            $this->assertEquals($fetched[$id]['name'], $data[$id][1], "record $id name is not '{$data[$id][1]}'");
            $this->assertEquals($fetched[$id]['idsite'], $data[$id][2], "record $id idsite is not '{$data[$id][2]}'");
            $this->assertEquals($fetched[$id]['date1'], $data[$id][3], "record $id date1 is not '{$data[$id][3]}'");
            $this->assertEquals($fetched[$id]['date2'], $data[$id][4], "record $id date2 is not '{$data[$id][4]}'");
            $this->assertEquals($fetched[$id]['period'], $data[$id][5], "record $id period is not '{$data[$id][5]}'");
            $this->assertEquals($fetched[$id]['ts_archived'], $data[$id][6], "record $id ts_archived is not '{$data[$id][6]}'");
            $this->assertEquals($fetched[$id]['value'], $data[$id][7], "record $id value is unexpected");
        }
    }

    /*
     * Schema for site_url table:
     *    site_url (
     *        idsite INTEGER(10) UNSIGNED NOT NULL,
     *        url VARCHAR(255) NOT NULL,
     *        PRIMARY KEY(idsite, url)
     *    )
     */
    protected function _getDataInsert()
    {
        return array(
            array(1, 'test'),
            array(2, 'te" \n st2'),
            array(3, " \n \r \t test"),

            // these aren't expected to work on a column of datatype VARCHAR
//            array(4, gzcompress( " \n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942")),
//            array(5, gzcompress('test4')),

            array(6, 'test5'),
            array(7, '简体中文'),
            array(8, '"'),
            array(9, "'"),
            array(10, '\\'),
            array(11, '\\"'),
            array(12, '\\\''),
            array(13, "\t"),
            array(14, "test \x00 null"),
            array(15, "\x00\x01\x02\0x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1a\x1b\x1c\x1d\x1e\x1f"),
        );
    }

    /**
     * see archive_blob table
     */
    protected function _getBlobDataInsert()
    {
        $ts = '2011-03-31 17:48:00';
        $str = '';
        for ($i = 0; $i < 256; $i++) {
            $str .= chr($i);
        }
        $array[] = array(1, 'bytes 0-255', 1, '2011-03-31', '2011-03-31', Piwik::$idPeriods['day'], $ts, $str);

        $array[] = array(2, 'compressed string', 1, '2011-03-31', '2011-03-31', Piwik::$idPeriods['day'], $ts, gzcompress(" \n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942\n \r \t teste eigaj oegheao geaoh guoea98742983 2 342942"));

        $str = file_get_contents(PIWIK_PATH_TEST_TO_ROOT . '/tests/resources/lipsum.txt');
        $array[] = array(3, 'lorem ipsum', 1, '2011-03-31', '2011-03-31', Piwik::$idPeriods['day'], $ts, $str);

        $array[] = array(4, 'lorem ipsum compressed', 1, '2011-03-31', '2011-03-31', Piwik::$idPeriods['day'], $ts, gzcompress($str));

        return $array;
    }

    public function provideContainerConfig()
    {
        return array(
            'Piwik\Access' => new FakeAccess()
        );
    }
}
