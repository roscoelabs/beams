<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\PrivacyManager\tests\Integration;

use Piwik\Archive;
use Piwik\Common;
use Piwik\Config;
use Piwik\DataAccess\RawLogDao;
use Piwik\Date;
use Piwik\Db;
use Piwik\LogDeleter;
use Piwik\DbHelper;
use Piwik\Option;
use Piwik\Plugins\Goals\API as APIGoals;
use Piwik\Plugins\Goals\Archiver;
use Piwik\Plugin\Dimension\DimensionMetadataProvider;
use Piwik\Plugins\PrivacyManager\LogDataPurger;
use Piwik\Plugins\PrivacyManager\PrivacyManager;
use Piwik\Plugins\PrivacyManager\ReportsPurger;
use Piwik\Plugins\VisitorInterest\API as APIVisitorInterest;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Tracker\GoalManager;
use Piwik\Tests\Framework\Fixture;

class DataPurgingTest_RawLogDao extends RawLogDao
{
    public $insertActionsOlderThanCallback;
    public $insertActionsNewerThanCallback;

    protected function insertActionsToKeep($maxIds, $olderThan = true, $insertIntoTempIterationStep = 100000)
    {
        parent::insertActionsToKeep($maxIds, $olderThan, 2); // we use 2 to force iterations during tests

        // allow code to be executed after data is inserted. for concurrency testing purposes.
        if ($olderThan && $this->insertActionsOlderThanCallback) {
            $callback = $this->insertActionsOlderThanCallback;
            $callback();
        } else if ($this->insertActionsNewerThanCallback) {
            $callback = $this->insertActionsNewerThanCallback;
            $callback();
        }
    }
}

/**
 * @group PrivacyManager
 * @group Plugins
 */
class DataPurgingTest extends IntegrationTestCase
{
    // constants used in checking whether numeric tables are populated correctly.
    // 'done' entries exist for every period, even if there's no metric data, so we need the
    // total archive count for each month.
    const TOTAL_JAN_ARCHIVE_COUNT = 37; // 31 + 4 + 1 + 1;
    const TOTAL_FEB_ARCHIVE_COUNT = 34; // 29 + 4 + 1;

    // the number of archive entries for a single metric if no purging is done. this #
    // is dependent on the number of periods for which there were visits.
    const JAN_METRIC_ARCHIVE_COUNT = 11; // 5 days + 4 weeks + 1 month + 1 year
    const FEB_METRIC_ARCHIVE_COUNT = 11; // 6 days + 4 weeks + 1 month

    const JAN_DONE_FLAGS_COUNT = 44;

    // fake metric/report name used to make sure unwanted metrics are purged
    const GARBAGE_FIELD = 'abcdefg';

    private static $idSite = 1;
    private static $dateTime = '2012-02-28';
    private static $daysAgoStart = 50;

    /**
     * @var PrivacyManager
     */
    private $instance = null;
    private $settings = null;

    private $unusedIdAction = null;

    public static function beforeTableDataCached()
    {
        parent::beforeTableDataCached();

        self::_addLogData();
        self::_addReportData();
    }

    protected static function configureFixture($fixture)
    {
        $fixture->createSuperUser = true;
    }

    public function setUp()
    {
        parent::setUp();

        LogDataPurger::$selectSegmentSize = 2;
        ReportsPurger::$selectSegmentSize = 2;

        Db::$lockPrivilegeGranted = null;

        $dateTime = Date::factory(self::$dateTime);

        // purging depends upon today's date, so 'older_than' parts must be dependent upon today
        $today = Date::factory('today');
        $daysSinceToday = ($today->getTimestamp() - $dateTime->getTimestamp()) / (24 * 60 * 60);

        $monthsSinceToday = 0;
        for ($date = $today; $date->toString('Y-m') != $dateTime->toString('Y-m'); $date = $date->subMonth(1)) {
            ++$monthsSinceToday;
        }

        // set default config
        $settings = array();
        $settings['delete_logs_enable'] = 1;

        // purging log data from before 2012-01-24
        $settings['delete_logs_older_than'] = 35 + $daysSinceToday;
        $settings['delete_logs_schedule_lowest_interval'] = 7;
        $settings['delete_logs_max_rows_per_query'] = 100000;
        $settings['delete_reports_enable'] = 1;
        $settings['delete_reports_older_than'] = $monthsSinceToday;
        $settings['delete_reports_keep_basic_metrics'] = 0;
        $settings['delete_reports_keep_day_reports'] = 0;
        $settings['delete_reports_keep_week_reports'] = 0;
        $settings['delete_reports_keep_month_reports'] = 0;
        $settings['delete_reports_keep_year_reports'] = 0;
        $settings['delete_reports_keep_range_reports'] = 0;
        $settings['delete_reports_keep_segment_reports'] = 0;
        PrivacyManager::savePurgeDataSettings($settings);
        $this->settings = $settings;
        $this->instance = new PrivacyManager();
    }

    public function tearDown()
    {
        parent::tearDown();

        $tempTableName = Common::prefixTable(RawLogDao::DELETE_UNUSED_ACTIONS_TEMP_TABLE_NAME);
        Db::query("DROP TABLE IF EXISTS " . $tempTableName);

        parent::tearDown();
    }

    /**
     * Make sure the first time deleteLogData is run, nothing happens.
     */
    public function testDeleteLogDataInitialRun()
    {
        $this->_checkNoDataChanges();

        // Check it does not run
        $this->assertFalse( $this->instance->deleteLogData() );

        // check that initial option is set
        $this->assertEquals(1, Option::get(PrivacyManager::OPTION_LAST_DELETE_PIWIK_LOGS_INITIAL));

        // perform other checks
        $this->_checkNoDataChanges();
    }

    /**
     * Make sure the first time deleteReportData is run, nothing happens.
     */
    public function testDeleteReportDataInitialRun()
    {
        $this->assertFalse($this->instance->deleteReportData() );

        // check that initial option is set
        $this->assertEquals(1, Option::get(PrivacyManager::OPTION_LAST_DELETE_PIWIK_LOGS_INITIAL));

        // perform other checks
        $this->_checkNoDataChanges();
    }

    /**
     * Make sure the task is not run when its scheduled for later.
     */
    public function testPurgeDataNotTimeToRun()
    {
        $yesterdaySecs = Date::factory('yesterday')->getTimestamp();

        Option::set(PrivacyManager::OPTION_LAST_DELETE_PIWIK_LOGS_INITIAL, 1);
        Option::set(PrivacyManager::OPTION_LAST_DELETE_PIWIK_LOGS, $yesterdaySecs);
        Option::set(PrivacyManager::OPTION_LAST_DELETE_PIWIK_REPORTS, $yesterdaySecs);
        $this->assertFalse( $this->instance->deleteLogData() );
        $this->assertFalse( $this->instance->deleteReportData() );

        // perform checks
        $this->_checkNoDataChanges();
    }

    /**
     * Make sure purging data runs when scheduled.
     */
    public function testPurgeDataNotInitialAndTimeToRun()
    {
        // get purge data prediction
        $prediction = PrivacyManager::getPurgeEstimate();

        // perform checks on prediction
        $events = 3; // only the event action for the three purged day, dayAgo=x are purged (others are still in use)
        $contents = 3; // one content impression per day, so 3 purged
        $expectedPrediction = array(
            Common::prefixTable('log_conversion')          => 6,
            Common::prefixTable('log_link_visit_action')   => 6 + $events + $contents,
            Common::prefixTable('log_visit')               => 3,
            Common::prefixTable('log_conversion_item')     => 3,
            Common::prefixTable('archive_numeric_2012_01') => -1,
            Common::prefixTable('archive_blob_2012_01')    => -1
        );
        $this->assertEquals($expectedPrediction, $prediction);

        // purge data
        $this->_setTimeToRun();
        $this->assertTrue( $this->instance->deleteLogData() );
        $this->assertTrue($this->instance->deleteReportData() );

        // perform checks
        $this->checkLogDataPurged();

        $archiveTables = self::_getArchiveTableNames();

        // January numeric table should be dropped
        $this->assertEquals(self::JAN_DONE_FLAGS_COUNT, $this->_getTableCount($archiveTables['numeric'][0])); // January

        // Check february metric count
        $febRowCount = $this->_getExpectedNumericArchiveCountFeb();
        $this->assertEquals($febRowCount, $this->_getTableCount($archiveTables['numeric'][1])); // February

        // January blob table should be dropped
        $this->assertEquals(0, $this->_getTableCount($archiveTables['blob'][0])); // January

        // Check february blob count (1 blob per period w/ visits + 1 garbage report)
        $this->assertEquals(self::FEB_METRIC_ARCHIVE_COUNT + 1, $this->_getTableCount($archiveTables['blob'][1])); // February
    }

    public function test_LogDataPurging_WorksWhenVisitsInPastTracked()
    {
        DbHelper::deleteArchiveTables();

        self::trackVisitInPast();
        self::_addReportData();

        $this->_setTimeToRun();
        $this->assertTrue( $this->instance->deleteLogData() );

        $this->checkLogDataPurged();

        // NOTE: it is not expected that the data purging estimate will work when visits in the past are tracked
    }

    /**
     * Make sure nothing happens when deleting logs & reports are both disabled.
     */
    public function testPurgeDataBothDisabled()
    {
        PrivacyManager::savePurgeDataSettings(array(
                                                         'delete_logs_enable'    => 0,
                                                         'delete_reports_enable' => 0
                                                    ));

        // get purge data prediction
        $prediction = PrivacyManager::getPurgeEstimate();

        // perform checks on prediction
        $expectedPrediction = array();
        $this->assertEquals($expectedPrediction, $prediction);

        // purge data
        $this->_setTimeToRun();
        $hasDeleted = $this->instance->deleteLogData();
        $this->assertFalse($hasDeleted);
        $this->assertFalse($this->instance->deleteReportData() );

        // perform checks
        $this->_checkNoDataChanges();
    }

    /**
     * Test that purgeData works when there's no data.
     */
    public function testPurgeDataDeleteLogsNoData()
    {
        DbHelper::truncateAllTables();
        DbHelper::deleteArchiveTables();

        // get purge data prediction
        $prediction = PrivacyManager::getPurgeEstimate();

        // perform checks on prediction
        $expectedPrediction = array();
        $this->assertEquals($expectedPrediction, $prediction);

        // purge data
        $this->_setTimeToRun();
        $this->assertTrue( $this->instance->deleteLogData() );

        $this->assertTrue($this->instance->deleteReportData() );

        // perform checks
        $this->assertEquals(0, $this->_getTableCount('log_visit'));
        $this->assertEquals(0, $this->_getTableCount('log_conversion'));
        $this->assertEquals(0, $this->_getTableCount('log_link_visit_action'));
        $this->assertEquals(0, $this->_getTableCount('log_conversion_item'));

        $archiveTables = self::_getArchiveTableNames();
        $this->assertFalse($this->_tableExists($archiveTables['numeric'][0])); // January
        $this->assertFalse($this->_tableExists($archiveTables['numeric'][1])); // February
        $this->assertFalse($this->_tableExists($archiveTables['blob'][0])); // January
        $this->assertFalse($this->_tableExists($archiveTables['blob'][1])); // February
    }

    /**
     * Test that purgeData works correctly when the 'keep basic metrics' setting is set to true.
     */
    public function testPurgeDataDeleteReportsKeepBasicMetrics()
    {
        PrivacyManager::savePurgeDataSettings(array(
                                                         'delete_reports_keep_basic_metrics' => 1
                                                    ));

        // get purge data prediction
        $prediction = PrivacyManager::getPurgeEstimate();

        // perform checks on prediction
        $events = 3; // only the event action for the three purged day, dayAgo=x are purged (others are still in use)
        $contents = 3; // one content impression per day, so 3 purged
        $expectedPrediction = array(
            Common::prefixTable('log_conversion')          => 6,
            Common::prefixTable('log_link_visit_action')   => 6 + $events + $contents,
            Common::prefixTable('log_visit')               => 3,
            Common::prefixTable('log_conversion_item')     => 3,
            Common::prefixTable('archive_numeric_2012_01') => 1, // remove the garbage metric
            Common::prefixTable('archive_blob_2012_01')    => -1
        );
        $this->assertEquals($expectedPrediction, $prediction);

        // purge data
        $this->_setTimeToRun();
        $this->assertTrue( $this->instance->deleteLogData() );
        $this->assertTrue($this->instance->deleteReportData() );

        // perform checks
        $this->checkLogDataPurged();

        $archiveTables = self::_getArchiveTableNames();

        // all numeric metrics should be saved except the garbage metric
        $janRowCount = $this->_getExpectedNumericArchiveCountJan() - 1;
        $tableName = $archiveTables['numeric'][0];
        $tableCount = $this->_getTableCount($tableName);
        $this->assertEquals($janRowCount, $tableCount); // January

        if ($janRowCount != $tableCount) {
            $this->dumpTable($tableName);
        }

        // check february numerics not deleted
        $febRowCount = $this->_getExpectedNumericArchiveCountFeb();
        $this->assertEquals($febRowCount, $this->_getTableCount($archiveTables['numeric'][1])); // February

        $this->assertEquals(0, $this->_getTableCount($archiveTables['blob'][0])); // January

        // check for no changes in the february blob table
        $this->assertEquals(self::FEB_METRIC_ARCHIVE_COUNT + 1, $this->_getTableCount($archiveTables['blob'][1])); // February
    }

    /**
     * Test that purgeData works correctly when the 'keep daily reports' setting is set to true.
     */
    public function testPurgeDataDeleteReportsKeepDailyReports()
    {
        PrivacyManager::savePurgeDataSettings(array(
                                                         'delete_reports_keep_day_reports' => 1
                                                    ));

        // get purge data prediction
        $prediction = PrivacyManager::getPurgeEstimate();

        // perform checks on prediction
        $events = 3; // only the event action for the three purged day, dayAgo=x are purged (others are still in use)
        $contents = 3; // one content impression per day, so 3 purged
        $expectedPrediction = array(
            Common::prefixTable('log_conversion')          => 6,
            Common::prefixTable('log_link_visit_action')   => 6 + $events + $contents,
            Common::prefixTable('log_visit')               => 3,
            Common::prefixTable('log_conversion_item')     => 3,
            Common::prefixTable('archive_numeric_2012_01') => -1,
            Common::prefixTable('archive_blob_2012_01')    => 10  // removing 4 weeks, 1 month & 1 year + 1 garbage report + 2 range reports + 1 segmented report
        );
        $this->assertEquals($expectedPrediction, $prediction);

        // purge data
        $this->_setTimeToRun();
        $this->assertTrue( $this->instance->deleteLogData() );
        $this->assertTrue($this->instance->deleteReportData() );

        // perform checks
        $this->checkLogDataPurged();
        $this->_checkReportsAndMetricsPurged($janBlobsRemaining = 5, $janNumericRemaining = 70); // 5 blobs for 5 days
    }

    /**
     * Test that purgeData works correctly when the 'keep weekly reports' setting is set to true.
     */
    public function testPurgeDataDeleteReportsKeepWeeklyReports()
    {
        PrivacyManager::savePurgeDataSettings(array(
                                                         'delete_reports_keep_week_reports' => 1
                                                    ));

        // get purge data prediction
        $prediction = PrivacyManager::getPurgeEstimate();

        // perform checks on prediction
        $events = 3; // only the event action for the three purged day, dayAgo=x are purged (others are still in use)
        $contents = 3; // one content impression per day, so 3 purged
        $expectedPrediction = array(
            Common::prefixTable('log_conversion')          => 6,
            Common::prefixTable('log_link_visit_action')   => 6 + $events + $contents,
            Common::prefixTable('log_visit')               => 3,
            Common::prefixTable('log_conversion_item')     => 3,
            Common::prefixTable('archive_numeric_2012_01') => -1,
            Common::prefixTable('archive_blob_2012_01')    => 11 // 5 days, 1 month & 1 year to remove + 1 garbage report + 2 range reports + 1 segmented report
        );
        $this->assertEquals($expectedPrediction, $prediction);

        // purge data
        $this->_setTimeToRun();
        $this->assertTrue( $this->instance->deleteLogData() );
        $this->assertTrue($this->instance->deleteReportData() );

        // perform checks
        $this->checkLogDataPurged();
        $this->_checkReportsAndMetricsPurged($janBlobsRemaining = 4, $janNumericRemaining = 64); // 4 blobs for 4 weeks
    }

    /**
     * Test that purgeData works correctly when the 'keep monthly reports' setting is set to true.
     */
    public function testPurgeDataDeleteReportsKeepMonthlyReports()
    {
        PrivacyManager::savePurgeDataSettings(array(
                                                         'delete_reports_keep_month_reports' => 1
                                                    ));

        // get purge data prediction
        $prediction = PrivacyManager::getPurgeEstimate();

        // perform checks on prediction
        $events = 3; // only the event action for the three purged day, dayAgo=x are purged (others are still in use)
        $contents = 3; // one content impression per day, so 3 purged
        $expectedPrediction = array(
            Common::prefixTable('log_conversion')          => 6,
            Common::prefixTable('log_link_visit_action')   => 6 + $events + $contents,
            Common::prefixTable('log_visit')               => 3,
            Common::prefixTable('log_conversion_item')     => 3,
            Common::prefixTable('archive_numeric_2012_01') => -1,
            Common::prefixTable('archive_blob_2012_01')    => 14 // 5 days, 4 weeks, 1 year to remove + 1 garbage report + 2 range reports + 1 segmented report
        );
        $this->assertEquals($expectedPrediction, $prediction);

        // purge data
        $this->_setTimeToRun();
        $this->assertTrue( $this->instance->deleteLogData() );
        $this->assertTrue($this->instance->deleteReportData() );

        // perform checks
        $this->checkLogDataPurged();
        $this->_checkReportsAndMetricsPurged($janBlobsRemaining = 1, $janNumericRemaining = 49);
    }

    /**
     * Test that purgeData works correctly when the 'keep yearly reports' setting is set to true.
     */
    public function testPurgeDataDeleteReportsKeepYearlyReports()
    {
        PrivacyManager::savePurgeDataSettings(array(
                                                         'delete_reports_keep_year_reports' => 1
                                                    ));

        // get purge data prediction
        $prediction = PrivacyManager::getPurgeEstimate();

        // perform checks on prediction
        $events = 3; // only the event action for the three purged day, dayAgo=x are purged (others are still in use)
        $contents = 3; // one content impression per day, so 3 purged
        $expectedPrediction = array(
            Common::prefixTable('log_conversion')          => 6,
            Common::prefixTable('log_link_visit_action')   => 6 + $events + $contents,
            Common::prefixTable('log_visit')               => 3,
            Common::prefixTable('log_conversion_item')     => 3,
            Common::prefixTable('archive_numeric_2012_01') => -1,
            Common::prefixTable('archive_blob_2012_01')    => 14  // 5 days, 4 weeks & 1 year to remove + 1 garbage report + 2 range reports + 1 segmented report
        );
        $this->assertEquals($expectedPrediction, $prediction);

        // purge data
        $this->_setTimeToRun();
        $this->assertTrue( $this->instance->deleteLogData() );
        $this->assertTrue($this->instance->deleteReportData() );

        // perform checks
        $this->checkLogDataPurged();
        $this->_checkReportsAndMetricsPurged($janBlobsRemaining = 1, $janNumericRemaining = 49);
    }

    /**
     * Test no concurrency issues when deleting log data from log_action table.
     */
    public function testPurgeLogDataConcurrency()
    {
        $rawLogDao = new DataPurgingTest_RawLogDao(new DimensionMetadataProvider());
        $rawLogDao->insertActionsOlderThanCallback = array($this, 'addReferenceToUnusedAction');
        $purger = new LogDataPurger(new LogDeleter($rawLogDao), $rawLogDao);

        $this->unusedIdAction = Db::fetchOne(
            "SELECT idaction FROM " . Common::prefixTable('log_action') . " WHERE name = ?",
            array('whatever.com/_40'));
        $this->assertTrue($this->unusedIdAction > 0);

        // purge data
        $purger->purgeData($this->settings['delete_logs_older_than'], $this->settings['delete_logs_max_rows_per_query']);

        // check that actions were purged
        $contentsNotPurged = 3;
        $this->assertEquals(22 + $this->getCountEventIdsNotPurged() + $contentsNotPurged, $this->_getTableCount('log_action')); // January

        // check that the unused action still exists
        $count = Db::fetchOne(
            "SELECT COUNT(*) FROM " . Common::prefixTable('log_action') . " WHERE idaction = ?",
            array($this->unusedIdAction));
        $this->assertEquals(1, $count);

        $this->unusedIdAction = null; // so the hook won't get executed twice
    }

    /**
     * Tests that purgeData works correctly when the 'keep range reports' setting is set to true.
     */
    public function testPurgeDataDeleteReportsKeepRangeReports()
    {
        PrivacyManager::savePurgeDataSettings(array(
                                                         'delete_reports_keep_range_reports' => 1
                                                    ));

        // get purge data prediction
        $prediction = PrivacyManager::getPurgeEstimate();

        // perform checks on prediction
        $events = 3; // only the event action for the three purged day, dayAgo=x are purged (others are still in use)
        $contents = 3; // one content impression per day, so 3 purged
        $expectedPrediction = array(
            Common::prefixTable('log_conversion')          => 6,
            Common::prefixTable('log_link_visit_action')   => 6 + $events + $contents,
            Common::prefixTable('log_visit')               => 3,
            Common::prefixTable('log_conversion_item')     => 3,
            Common::prefixTable('archive_numeric_2012_01') => -1,
            Common::prefixTable('archive_blob_2012_01')    => 13  // 5 days, 4 weeks, 1 month & 1 year + 1 garbage report + 1 segmented report
        );
        $this->assertEquals($expectedPrediction, $prediction);

        // purge data
        $this->_setTimeToRun();
        $this->assertTrue( $this->instance->deleteLogData() );
        $this->assertTrue($this->instance->deleteReportData() );

        // perform checks
        $this->checkLogDataPurged();
        $this->_checkReportsAndMetricsPurged($janBlobsRemaining = 2, $janNumericRemaining = 48); // 2 range blobs
    }

    /**
     * Tests that purgeData works correctly when the 'keep segment reports' setting is set to true.
     */
    public function testPurgeDataDeleteReportsKeepSegmentsReports()
    {
        PrivacyManager::savePurgeDataSettings(array(
                                                         'delete_reports_keep_day_reports'     => 1,
                                                         'delete_reports_keep_segment_reports' => 1
                                                    ));

        // get purge data prediction
        $prediction = PrivacyManager::getPurgeEstimate();

        // perform checks on prediction
        $events = 3; // only the event action for the three purged day, dayAgo=x are purged (others are still in use)
        $contents = 3; // one content impression per day, so 3 purged
        $expectedPrediction = array(
            Common::prefixTable('log_conversion')          => 6,
            Common::prefixTable('log_link_visit_action')   => 6 + $events + $contents,
            Common::prefixTable('log_visit')               => 3,
            Common::prefixTable('log_conversion_item')     => 3,
            Common::prefixTable('archive_numeric_2012_01') => -1,
            Common::prefixTable('archive_blob_2012_01')    => 9 // 4 weeks, 1 month & 1 year + 1 garbage report + 2 range reports
        );
        $this->assertEquals($expectedPrediction, $prediction);

        // purge data
        $this->_setTimeToRun();
        $this->assertTrue( $this->instance->deleteLogData() );
        $this->assertTrue($this->instance->deleteReportData() );

        // perform checks
        $this->checkLogDataPurged();
        $this->_checkReportsAndMetricsPurged($janBlobsRemaining = 6, $janNumericRemaining = 72); // 1 segmented blob + 5 day blobs
    }

    // --- utility functions follow ---

    protected static function _addLogData()
    {
        // tracks visits on the following days:
        // - 2012-01-09
        // - 2012-01-14
        // - 2012-01-19
        // - 2012-01-24 <--- everything before this date is to be purged
        // - 2012-01-29
        // - 2012-02-03
        // - 2012-02-08
        // - 2012-02-13
        // - 2012-02-18
        // - 2012-02-23
        // - 2012-02-28
        // 6 visits in feb, 5 in jan

        // following actions are created:
        // - 'First page view'
        // - 'Second page view'
        // - 'SKU2'
        // - 'Canon SLR'
        // - 'Electronics & Cameras'
        // - for every visit (11 visits total):
        //   - http://whatever.com/_{$daysSinceLastVisit}
        //   - http://whatever.com/42/{$daysSinceLastVisit}

        $start = Date::factory(self::$dateTime);
        self::$idSite = Fixture::createWebsite('2012-01-01', $ecommerce = 1);
        APIGoals::getInstance()->addGoal(self::$idSite, 'match all', 'url', 'http', 'contains');

        $t = Fixture::getTracker(self::$idSite, $start, $defaultInit = true);
        $t->enableBulkTracking();
        $t->setTokenAuth(Fixture::getTokenAuth());

        for ($daysAgo = self::$daysAgoStart; $daysAgo >= 0; $daysAgo -= 5) // one visit every 5 days
        {
            $dateTime = $start->subDay($daysAgo)->toString();

            $t->setForceVisitDateTime($dateTime);
            $t->setUserAgent('Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.9.2.6) Gecko/20100625 Firefox/3.6.6 (.NET CLR 3.5.30729)');

            // use $daysAgo to make sure new actions are created for every day and aren't used again.
            // when deleting visits, some of these actions will no longer be referenced in the DB.
            $t->setUrl("http://whatever.com/_$daysAgo");
            $t->doTrackPageView('First page view');

            $t->setForceVisitDateTime(Date::factory($dateTime)->addHour(0.05));
            $t->setUrl("http://whatever.com/42/$daysAgo");
            $t->doTrackPageView('Second page view');

            // track an event to test event actions are purged/preserved
            $t->setForceVisitDateTime(Date::factory($dateTime)->addHour(0.1));
            $t->setUrl("http://whatever.com/event");
            $t->doTrackEvent('Event action', 'event cat', 'daysAgo=' . $daysAgo, 1000);

            // track a content impression to test that content actions are purged/preserved
            $t->setForceVisitDateTime(Date::factory($dateTime)->addHour(0.1));
            $t->doTrackContentImpression('SugarTransportAd', '/path/ad.jpg', 'http://www.satsumaprovince.jp');

            $t->setForceVisitDateTime(Date::factory($dateTime)->addHour(0.2));
            $t->addEcommerceItem($sku = 'SKU2', $name = 'Canon SLR', $category = 'Electronics & Cameras',
                $price = 1500, $quantity = 1);
            $t->doTrackEcommerceOrder($orderId = '937nsjusu ' . $dateTime, $grandTotal = 1111.11, $subTotal = 1000,
                $tax = 111, $shipping = 0.11, $discount = 666);
        }

        Fixture::checkBulkTrackingResponse($t->doBulkTrack());
    }

    protected static function trackVisitInPast()
    {
        $start = Date::factory(self::$dateTime);

        // add a visit in the past so the idvisit will be greater than the others, but the time will be older
        // this tests issue #7180
        $t = Fixture::getTracker(self::$idSite, $start, $defaultInit = true);
        // we subtract 5 so it will be on the same day as another visit. this way, we won't create another day archive
        // and change the counts in asserts
        $t->setForceVisitDateTime($start->subDay(self::$daysAgoStart - 5));
        $t->setUrl("http://whatever.com/days_in_past");
        $t->doTrackPageView('visit in past');
    }

    protected static function _addReportData()
    {
        $date = Date::factory(self::$dateTime);

        Archive::build(self::$idSite, 'year', $date);

        APIVisitorInterest::getInstance()->getNumberOfVisitsPerVisitDuration(self::$idSite, 'year', $date);

        // months are added via the 'year' period, but weeks must be done manually
        for ($daysAgo = self::$daysAgoStart; $daysAgo > 0; $daysAgo -= 7) // every week
        {
            $dateTime = $date->subDay($daysAgo);

            $archive = Archive::build(self::$idSite, 'week', $dateTime);
            $archive->getNumeric('nb_visits');

            APIVisitorInterest::getInstance()->getNumberOfVisitsPerVisitDuration(
                self::$idSite, 'week', $dateTime);
        }

        // add segment for one day
        $archive = Archive::build(self::$idSite, 'day', '2012-01-14', 'browserCode==FF');
        $archive->getNumeric('nb_visits', 'nb_hits');

        APIVisitorInterest::getInstance()->getNumberOfVisitsPerVisitDuration(
            self::$idSite, 'day', '2012-01-14', 'browserCode==FF');

        // add range within January
        $rangeEnd = Date::factory('2012-01-29');
        $rangeStart = $rangeEnd->subDay(1);
        $range = $rangeStart->toString('Y-m-d') . "," . $rangeEnd->toString('Y-m-d');

        $rangeArchive = Archive::build(self::$idSite, 'range', $range);
        $rangeArchive->getNumeric('nb_visits', 'nb_hits');

        APIVisitorInterest::getInstance()->getNumberOfVisitsPerVisitDuration(self::$idSite, 'range', $range);

        // add range between January & February
        $rangeStart = $rangeEnd;
        $rangeEnd = $rangeStart->addDay(3);
        $range = $rangeStart->toString('Y-m-d') . "," . $rangeEnd->toString('Y-m-d');

        $rangeArchive = Archive::build(self::$idSite, 'range', $range);
        $rangeArchive->getNumeric('nb_visits', 'nb_hits');

        APIVisitorInterest::getInstance()->getNumberOfVisitsPerVisitDuration(self::$idSite, 'range', $range);

        // when archiving is initiated, the archive metrics & reports for EVERY loaded plugin
        // are archived. don't want this test to depend on every possible metric, so get rid of
        // the unwanted archive data now.
        $metricsToSave = array(
            'nb_visits',
            'nb_actions',
            Archiver::getRecordName('revenue'),
            Archiver::getRecordName('nb_conversions', 1),
            Archiver::getRecordName('revenue', GoalManager::IDGOAL_ORDER)
        );

        $archiveTables = self::_getArchiveTableNames();
        foreach ($archiveTables['numeric'] as $table) {
            $realTable = Common::prefixTable($table);
            $sql = "DELETE FROM $realTable WHERE name NOT IN ('" . implode("','", $metricsToSave) . "') AND name NOT LIKE 'done%'";
            Db::query($sql);
        }
        foreach ($archiveTables['blob'] as $table) {
            $realTable = Common::prefixTable($table);
            Db::query("DELETE FROM $realTable WHERE name NOT IN ('VisitorInterest_timeGap')");
        }

        // add garbage metrics
        $janDate1 = '2012-01-05';
        $febDate1 = '2012-02-04';

        $sql = "INSERT INTO %s (idarchive,name,idsite,date1,date2,period,ts_archived,value)
                        VALUES (10000,?,1,?,?,?,?,?)";

        // one metric for jan & one for feb
        Db::query(sprintf($sql, Common::prefixTable($archiveTables['numeric'][0])),
            array(self::GARBAGE_FIELD, $janDate1, $janDate1, 1, $janDate1, 100));
        Db::query(sprintf($sql, Common::prefixTable($archiveTables['numeric'][1])),
            array(self::GARBAGE_FIELD, $febDate1, $febDate1, 1, $febDate1, 200));

        // add garbage reports
        Db::query(sprintf($sql, Common::prefixTable($archiveTables['blob'][0])),
            array(self::GARBAGE_FIELD, $janDate1, $janDate1, 10, $janDate1, 'blobval'));
        Db::query(sprintf($sql, Common::prefixTable($archiveTables['blob'][1])),
            array(self::GARBAGE_FIELD, $febDate1, $febDate1, 20, $febDate1, 'blobval'));
    }

    protected function _checkNoDataChanges()
    {
        // 11 visits total w/ 4 actions per visit & 2 conversions per visit. 1 e-commerce order per visit.
        $this->assertEquals(11, $this->_getTableCount('log_visit'));
        $this->assertEquals(22, $this->_getTableCount('log_conversion'));
        $this->assertEquals(44, $this->_getTableCount('log_link_visit_action'));
        $this->assertEquals(11, $this->_getTableCount('log_conversion_item'));
        $this->assertEquals(45, $this->_getTableCount('log_action'));

        $archiveTables = self::_getArchiveTableNames();

        $janMetricCount = $this->_getExpectedNumericArchiveCountJan();
        $this->assertEquals($janMetricCount, $this->_getTableCount($archiveTables['numeric'][0])); // January

        // no range metric for february
        $febMetricCount = $this->_getExpectedNumericArchiveCountFeb();
        $this->assertEquals($febMetricCount, $this->_getTableCount($archiveTables['numeric'][1])); // February

        // 1 entry per period w/ visits + 1 garbage report + 2 range reports + 1 segment report
        $this->assertEquals(self::JAN_METRIC_ARCHIVE_COUNT + 1 + 2 + 1, $this->_getTableCount($archiveTables['blob'][0])); // January
        $this->assertEquals(self::FEB_METRIC_ARCHIVE_COUNT + 1, $this->_getTableCount($archiveTables['blob'][1])); // February
    }

    /**
     * Helper method. Performs checks after reports are purged. Checks that the january numeric table
     * was dropped, that the february metric & blob tables are unaffected, and that the january blob
     * table has a certain number of blobs.
     */
    protected function _checkReportsAndMetricsPurged($janBlobsRemaining, $janNumericRemaining)
    {
        $archiveTables = self::_getArchiveTableNames();

        $this->assertEquals($janNumericRemaining, $this->_getTableCount($archiveTables['numeric'][0]));

        // check february numerics not deleted
        $febRowCount = $this->_getExpectedNumericArchiveCountFeb();
        $this->assertEquals($febRowCount, $this->_getTableCount($archiveTables['numeric'][1])); // February

        // check the january blob count
        $this->assertEquals($janBlobsRemaining, $this->_getTableCount($archiveTables['blob'][0])); // January

        // check for no changes in the february blob table (1 blob for every period w/ visits in feb + 1 garbage report)
        $this->assertEquals(self::FEB_METRIC_ARCHIVE_COUNT + 1, $this->_getTableCount($archiveTables['blob'][1])); // February
    }

    private function checkLogDataPurged()
    {
        // 3 days removed by purge, so 3 visits, 6 conversions, 6 visit actions, 3 e-commerce orders
        // & 6 actions removed
        $events = 11 - 3; // 3 deleted (1 per day purged)
        $contents = 11 - 3; // 3 deleted (1 per day purged)
        $this->assertEquals(8, $this->_getTableCount('log_visit'));
        $this->assertEquals(16, $this->_getTableCount('log_conversion'));
        $this->assertEquals(16 + $events + $contents, $this->_getTableCount('log_link_visit_action'));
        $this->assertEquals(8, $this->_getTableCount('log_conversion_item'));

        $eventsId = $this->getCountEventIdsNotPurged();
        $contentsNotPurged = 3;
        $this->assertEquals(21 + $eventsId + $contentsNotPurged, $this->_getTableCount('log_action'));
    }

    /**
     * Event hook that adds a row into the DB that references unused idaction AFTER LogDataPurger
     * does the insert into the temporary table. When log_actions are deleted, this idaction should still
     * be kept. w/ the wrong strategy, it won't be and there will be a dangling reference
     * in the log_link_visit_action table.
     */
    public function addReferenceToUnusedAction()
    {
        $unusedIdAction = $this->unusedIdAction;
        if (empty($unusedIdAction)) // make sure we only do this for one test case
        {
            return;
        }

        $logLinkVisitActionTable = Common::prefixTable("log_link_visit_action");

        $sql = "INSERT INTO $logLinkVisitActionTable
                            (idsite, idvisitor, server_time, idvisit, idaction_url, idaction_url_ref,
                            idaction_name, idaction_name_ref, time_spent_ref_action)
                     VALUES (1, 'abc', NOW(), 15, $unusedIdAction, $unusedIdAction,
                             $unusedIdAction, $unusedIdAction, 1000)";

        Db::query($sql);
    }

    protected function _setTimeToRun()
    {
        $lastDateSecs = Date::factory('today')->subDay(8)->getTimestamp();

        Option::set(PrivacyManager::OPTION_LAST_DELETE_PIWIK_LOGS_INITIAL, 1);
        Option::set(PrivacyManager::OPTION_LAST_DELETE_PIWIK_LOGS, $lastDateSecs);
        Option::set(PrivacyManager::OPTION_LAST_DELETE_PIWIK_REPORTS, $lastDateSecs);
    }

    protected function _getTableCount($tableName, $where = '')
    {
        $sql = "SELECT COUNT(*) FROM " . Common::prefixTable($tableName) . " $where";
        return Db::fetchOne($sql);
    }

    protected function dumpTable($tableName, $where = '')
    {
        $sql = "SELECT * FROM " . Common::prefixTable($tableName) . " $where";
        var_export(Db::fetchAll($sql));
    }

    protected function _tableExists($tableName)
    {
        $dbName = Config::getInstance()->database['dbname'];

        $sql = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?";
        return Db::fetchOne($sql, array($dbName, Common::prefixTable($tableName))) == 1;
    }

    protected static function _getArchiveTableNames()
    {
        return array(
            'numeric' => array(
                'archive_numeric_2012_01',
                'archive_numeric_2012_02'
            ),
            'blob'    => array(
                'archive_blob_2012_01',
                'archive_blob_2012_02'
            )
        );
    }

    protected function _getExpectedNumericArchiveCountJan()
    {
        // 5 entries per period w/ visits
        // + 1 entry for every period in the month (the 'done' rows)
        // + 1 garbage metric
        // log_link_visit_action+ 2 entries per range period (4 total) + 2 'done...' entries per range period (4 total)
        // + 2 entries per segment (2 total) + 2 'done...' entries per segment (2 total)
        // +1 done flag for one further week used to create the archive of a month
        return self::JAN_METRIC_ARCHIVE_COUNT * 5 + self::TOTAL_JAN_ARCHIVE_COUNT + 1 + 8 + 4 + 1;
    }

    protected function _getExpectedNumericArchiveCountFeb()
    {
        // (5 metrics per period w/ visits
        // + 1 'done' archive for every period)
        // + 1 garbage metric
        return self::FEB_METRIC_ARCHIVE_COUNT * 5 + self::TOTAL_FEB_ARCHIVE_COUNT + 1;
    }

    /**
     * @return int
     */
    private function getCountEventIdsNotPurged()
    {
        $count = 11 /* days eventAction */ + 2 /* category+name */ + 1 /* event url */ - 3 /* days deleted */;
        $count += 1; // since content tracking is done after event tracking, a referrer action is generated for the event
        return $count;
    }
}