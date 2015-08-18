<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\System;

use Piwik\Common;
use Piwik\Config;
use Piwik\Db;
use Piwik\Option;
use Piwik\Plugins\UserCountry\LocationProvider;
use Piwik\Scheduler\Schedule\Schedule;
use Piwik\Scheduler\Task;
use Piwik\Scheduler\Timetable;
use Piwik\SettingsPiwik;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Tracker;

/**
 * @group Core
 * @group Tracker
 */
class TrackerTest extends IntegrationTestCase
{
    const TASKS_FINISHED_OPTION_NAME = "Tests.scheduledTasksFinished";
    const TASKS_STARTED_OPTION_NAME = "Tests.scheduledTasksStarted";

    public function setUp()
    {
        parent::setUp();

        Fixture::createWebsite('2014-02-04');

        $testingEnvironment = new \Piwik\Tests\Framework\TestingEnvironmentVariables();
        $testingEnvironment->testCaseClass = null;
        $testingEnvironment->addFailingScheduledTask = false;
        $testingEnvironment->addScheduledTask = false;
        $testingEnvironment->save();

        Option::delete(self::TASKS_STARTED_OPTION_NAME);
        Option::delete(self::TASKS_FINISHED_OPTION_NAME);
        Option::delete(Timetable::TIMETABLE_OPTION_STRING);

        SettingsPiwik::overwritePiwikUrl(self::$fixture->getRootUrl() . "tests/PHPUnit/proxy");
    }

    protected static function configureFixture($fixture)
    {
        $fixture->createSuperUser = true;
    }

    /**
     * Test the Bulk tracking API as documented in: http://developer.piwik.org/api-reference/tracking-api#bulk-tracking
     *
     * With invalid token_auth the request would still work
     */
    public function test_trackingApiWithBulkRequests_viaCurl_withWrongTokenAuth()
    {
        $token_auth = '33dc3f2536d3025974cccb4b4d2d98f4';
        $this->issueBulkTrackingRequest($token_auth, $expectTrackingToSucceed = true);
    }

    public function test_trackingApiWithBulkRequests_viaCurl_withCorrectTokenAuth()
    {
        $token_auth = Fixture::getTokenAuth();
        \Piwik\Filesystem::deleteAllCacheOnUpdate();
        $this->issueBulkTrackingRequest($token_auth, $expectTrackingToSucceed = true);
    }

    public function test_trackingEcommerceOrder_WithHtmlEscapedText_InsertsCorrectLogs()
    {
        // item sku, item name, item category, item price, item quantity
        // NOTE: used to test with '&#x1D306;' character, however, mysql on travis fails with this when
        //       inserting this character decoded.
        $ecItems = array(array('&quot;scarysku', 'superscarymovie&quot;', 'scary &amp; movies', 12.99, 1),
                         array('&gt; scary', 'but &lt; &quot;super', 'scary&quot;', 14, 15),
                         array("&#x27;Foo &#xA9;", " bar ", " baz &#x2603; qux", 16, 17));

        $urlToTest = $this->getEcommerceItemsUrl($ecItems);

        $response = $this->sendTrackingRequestByCurl($urlToTest);
        Fixture::checkResponse($response);

        $this->assertEquals(1, $this->getCountOfConversions());

        $conversionItems = $this->getConversionItems();
        $this->assertEquals(3, count($conversionItems));

        $this->assertActionEquals('"scarysku', $conversionItems[0]['idaction_sku']);
        $this->assertActionEquals('superscarymovie"', $conversionItems[0]['idaction_name']);
        $this->assertActionEquals('scary & movies', $conversionItems[0]['idaction_category']);

        $this->assertActionEquals('> scary', $conversionItems[1]['idaction_sku']);
        $this->assertActionEquals('but < "super', $conversionItems[1]['idaction_name']);
        $this->assertActionEquals('scary"', $conversionItems[1]['idaction_category']);

        $this->assertActionEquals('\'Foo ©', $conversionItems[2]['idaction_sku']);
        $this->assertActionEquals('bar', $conversionItems[2]['idaction_name']);
        $this->assertActionEquals('baz ☃ qux', $conversionItems[2]['idaction_category']);
    }

    public function test_trackingEcommerceOrder_WithAmpersandAndQuotes_InsertsCorrectLogs()
    {
        // item sku, item name, item category, item price, item quantity
        $ecItems = array(array("\"scarysku&", "superscarymovie'", 'scary <> movies', 12.99, 1));

        $urlToTest = $this->getEcommerceItemsUrl($ecItems);

        $response = $this->sendTrackingRequestByCurl($urlToTest);
        Fixture::checkResponse($response);

        $this->assertEquals(1, $this->getCountOfConversions());

        $conversionItems = $this->getConversionItems();
        $this->assertEquals(1, count($conversionItems));

        $this->assertActionEquals('"scarysku&', $conversionItems[0]['idaction_sku']);
        $this->assertActionEquals('superscarymovie\'', $conversionItems[0]['idaction_name']);
        $this->assertActionEquals('scary <> movies', $conversionItems[0]['idaction_category']);
    }

    public function test_trackingEcommerceOrder_DoesNotFail_WhenEmptyEcommerceItemsParamUsed()
    {
        // item sku, item name, item category, item price, item quantity
        $urlToTest = $this->getEcommerceItemsUrl("");

        $response = $this->sendTrackingRequestByCurl($urlToTest);
        Fixture::checkResponse($response);

        $this->assertEquals(1, $this->getCountOfConversions());
        $this->assertEquals(0, count($this->getConversionItems()));
    }

    public function test_trackingEcommerceOrder_DoesNotFail_WhenNonArrayUsedWithEcommerceItemsParam()
    {
        // item sku, item name, item category, item price, item quantity
        $urlToTest = $this->getEcommerceItemsUrl("45");

        $response = $this->sendTrackingRequestByCurl($urlToTest);
        Fixture::checkResponse($response);

        $this->assertEquals(0, $this->getCountOfConversions());
        $this->assertEquals(0, count($this->getConversionItems()));
    }

    public function test_trackingWithLangParameter_ForwardsLangParameter_ToDefaultLocationProvider()
    {
        LocationProvider::setCurrentProvider(LocationProvider\DefaultProvider::ID);

        $urlToTest = "?idsite=1&rec=1&action_name=test&lang=fr-be";
        $response = $this->sendTrackingRequestByCurl($urlToTest);
        Fixture::checkResponse($response);

        $logVisitTable = Common::prefixTable('log_visit');

        $visitCount = Db::fetchOne("SELECT COUNT(*) FROM $logVisitTable");
        $this->assertEquals(1, $visitCount);

        $visitCountry = Db::fetchOne("SELECT location_country FROM $logVisitTable");
        $this->assertEquals('be', $visitCountry);
    }

    public function test_scheduledTasks_CanBeRunThroughTracker_WithoutIncludingOutputInTrackerOutput()
    {
        $this->setScheduledTasksToRunInTracker();

        $urlToTest = $this->getSimpleTrackingUrl();

        $response = $this->sendTrackingRequestByCurl($urlToTest);
        Fixture::checkResponse($response);

        $this->assertScheduledTasksWereRun();
    }

    public function test_scheduledTasks_CanBeRunThroughTracker_WithOutputIncluded_IfDebugQueryParamUsed()
    {
        $this->setScheduledTasksToRunInTracker();

        $urlToTest = $this->getSimpleTrackingUrl() . '&debug=1';

        $response = $this->sendTrackingRequestByCurl($urlToTest);

        $this->assertScheduledTasksWereRun();

        $this->assertContains('Scheduled Tasks: Starting...', $response);
    }

    public function getTypesOfErrorsForScheduledTasksTrackerFailureTest()
    {
        return array(
            array(true),
            array(false)
        );
    }

    /**
     * @dataProvider getTypesOfErrorsForScheduledTasksTrackerFailureTest
     */
    public function test_scheduledTasks_DoNotFailTracking_WhenScheduledTaskFails($doFatalError)
    {
        $this->setScheduledTasksToRunInTracker();
        $this->addFailingScheduledTaskToTracker($doFatalError);

        $urlToTest = $this->getSimpleTrackingUrl();

        $response = $this->sendTrackingRequestByCurl($urlToTest);
        Fixture::checkResponse($response);

        if ($doFatalError) {
            $this->assertScheduledTasksWereNotRun();
        } else {
            $this->assertScheduledTasksWereRun();
        }
    }

    protected function issueBulkTrackingRequest($token_auth, $expectTrackingToSucceed)
    {
        $piwikHost = Fixture::getRootUrl() . 'tests/PHPUnit/proxy/piwik.php';

        $command = 'curl -s -X POST -d \'{"requests":["?idsite=1&url=http://example.org&action_name=Test bulk log Pageview&rec=1","?idsite=1&url=http://example.net/test.htm&action_name=Another bulk page view&rec=1"],"token_auth":"' . $token_auth . '"}\' ' . $piwikHost;

        exec($command, $output, $result);
        if ($result !== 0) {
            throw new \Exception("tracking bulk failed: " . implode("\n", $output) . "\n\ncommand used: $command");
        }
        $output = implode("", $output);
        $this->assertStringStartsWith('{"status":', $output);

        if($expectTrackingToSucceed) {
            $this->assertNotContains('error', $output);
            $this->assertContains('success', $output);
        } else {
            $this->assertContains('error', $output);
            $this->assertNotContains('success', $output);
        }
    }

    private function sendTrackingRequestByCurl($url)
    {
        if (!function_exists('curl_init')) {
            $this->markTestSkipped('Curl is not installed');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, Fixture::getRootUrl() . 'tests/PHPUnit/proxy/piwik.php' . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $response = substr($response, $headerSize);

        curl_close($ch);

        return $response;
    }

    private function assertActionEquals($expected, $idaction)
    {
        $actionName = Db::fetchOne("SELECT name FROM " . Common::prefixTable('log_action') . " WHERE idaction = ?", array($idaction));
        $this->assertEquals($expected, $actionName);
    }

    private function getCountOfConversions()
    {
        return Db::fetchOne("SELECT COUNT(*) FROM " . Common::prefixTable('log_conversion'));
    }

    private function getConversionItems()
    {
        return Db::fetchAll("SELECT * FROM " . Common::prefixTable('log_conversion_item'));
    }

    private function getEcommerceItemsUrl($ecItems, $doJsonEncode = true)
    {
        $ecItemsStr = $doJsonEncode ? json_encode($ecItems) : $ecItems;
        return "?idsite=1&idgoal=0&rec=1&url=" . urlencode('http://quellehorreur.com/movies') . "&ec_items="
        . urlencode($ecItemsStr) . '&ec_id=myspecial-id-1234&revenue=16.99&ec_st=12.99&ec_tx=0&ec_sh=3';
    }

    private function getSimpleTrackingUrl()
    {
        return "?idsite=1&rec=1&url=" . urlencode('http://quellehorreur.com/movies') . "&action_name=Movies";
    }

    private function setScheduledTasksToRunInTracker()
    {
        $testingEnvironment = new \Piwik\Tests\Framework\TestingEnvironmentVariables();
        $testingEnvironment->testCaseClass = 'Piwik\Tests\System\TrackerTest';
        $testingEnvironment->addScheduledTask = true;
        $testingEnvironment->configOverride = array('Tracker' => array('scheduled_tasks_min_interval' => 1, 'debug_on_demand' => 1));
        $testingEnvironment->save();
    }

    private function addFailingScheduledTaskToTracker($doFatalError)
    {
        $testingEnvironment = new \Piwik\Tests\Framework\TestingEnvironmentVariables();
        $testingEnvironment->addFailingScheduledTask = true;
        $testingEnvironment->scheduledTaskFailureShouldBeFatal = $doFatalError;
        $testingEnvironment->save();
    }

    public function provideContainerConfig()
    {
        if (!defined('DEBUG_FORCE_SCHEDULED_TASKS')) {
            define('DEBUG_FORCE_SCHEDULED_TASKS', 1);
        }

        $testingEnvironment = new \Piwik\Tests\Framework\TestingEnvironmentVariables();

        $tasksToAdd = array();

        if ($testingEnvironment->addFailingScheduledTask) {
            if ($testingEnvironment->scheduledTaskFailureShouldBeFatal) {
                $tasksToAdd[] = new Task($this, 'triggerFatalError', null, Schedule::factory('hourly'));
            } else {
                $tasksToAdd[] = new Task($this, 'throwScheduledTaskException', null, Schedule::factory('hourly'));
            }
        }

        if ($testingEnvironment->addScheduledTask) {
            $tasksToAdd[] = new Task($this, 'markScheduledTaskExecutionFinished', null, Schedule::factory('hourly'));
        }

        $result = array();
        if (!empty($tasksToAdd)) {
            $initialTask = new Task($this, 'markCustomTaskExecuted', null, Schedule::factory('hourly'));
            $tasksToAdd = array_merge(array($initialTask), $tasksToAdd);

            $mockTaskLoader = $this->getMock('Piwik\Scheduler\TaskLoader', array('loadTasks'));
            $mockTaskLoader->expects($this->any())->method('loadTasks')->will($this->returnValue($tasksToAdd));
            $result['Piwik\Scheduler\TaskLoader'] = $mockTaskLoader;
        }
        return $result;
    }

    public function triggerFatalError()
    {
        require 'thehowling';
    }

    public function throwScheduledTaskException()
    {
        throw new \Exception("triggered exception");
    }

    public function markScheduledTaskExecutionFinished()
    {
        Option::set(self::TASKS_FINISHED_OPTION_NAME, 1);
    }

    public function markCustomTaskExecuted()
    {
        Option::set(self::TASKS_STARTED_OPTION_NAME, 1);
    }

    private function assertScheduledTasksWereRun()
    {
        $this->assertCustomTasksWereStarted();

        Option::clearCachedOption(self::TASKS_FINISHED_OPTION_NAME);
        $this->assertEquals(1, Option::get(self::TASKS_FINISHED_OPTION_NAME));
    }

    private function assertScheduledTasksWereNotRun()
    {
        $this->assertCustomTasksWereStarted();

        Option::clearCachedOption(self::TASKS_FINISHED_OPTION_NAME);
        $this->assertEmpty(Option::get(self::TASKS_FINISHED_OPTION_NAME));
    }

    private function assertCustomTasksWereStarted()
    {
        Option::clearCachedOption(self::TASKS_STARTED_OPTION_NAME);
        $this->assertEquals(1, Option::get(self::TASKS_STARTED_OPTION_NAME));
    }
}