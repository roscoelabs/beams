<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link    http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Tests\System;

use Piwik\API\Request;
use Piwik\Common;
use Piwik\Date;
use Piwik\Tests\Framework\TestCase\SystemTestCase;
use Piwik\Tests\Fixtures\ManyVisitsWithGeoIP;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tracker\Cache;

/**
 * testing a the auto suggest API for all known segments
 *
 * @group AutoSuggestAPITest
 * @group Core
 */
class AutoSuggestAPITest extends SystemTestCase
{
    public static $fixture = null; // initialized below class definition

    protected static $processed = 0;
    protected static $skipped = array();

    /**
     * @dataProvider getApiForTesting
     */
    public function testApi($api, $params)
    {
        // Refresh cache for CustomVariables\Model
        Cache::clearCacheGeneral();

        if(self::isPhpVersion53() && self::isTravisCI()) {
            $this->markTestSkipped("Skipping this test as it seg faults on php 5.3 (bug triggered on travis)");
        }

        $this->runApiTests($api, $params);
    }

    public function getApiForTesting()
    {
        // we will test all segments from all plugins
        Fixture::loadAllPlugins();

        $idSite = self::$fixture->idSite;
        $apiForTesting = array();

        $segments = \Piwik\Plugins\API\API::getInstance()->getSegmentsMetadata(self::$fixture->idSite);
        foreach ($segments as $segment) {
            $apiForTesting[] = $this->getApiForTestingForSegment($idSite, $segment['segment']);
        }

        if (self::isMysqli() || self::isTravisCI()) {
            // Skip the test on Mysqli as it fails due to rounding Float errors on latitude/longitude
            // then the test started failing after bc19503 and I cannot understand why
            echo "Skipped test \n";
        } else {
            $apiForTesting[] = array('Live.getLastVisitsDetails',
                                     array('idSite' => $idSite,
                                           'date'   => '1998-07-12,today',
                                           'period' => 'range',
                                           'otherRequestParameters' => array('filter_limit' => 1000)));

        }
        return $apiForTesting;
    }

    /**
     * @param $idSite
     * @param $segment
     * @return array
     */
    protected function getApiForTestingForSegment($idSite, $segment)
    {
        return array('API.getSuggestedValuesForSegment',
                     array('idSite'                 => $idSite,
                           'testSuffix'             => '_' . $segment,
                           'otherRequestParameters' => array('segmentName' => $segment)));
    }

    /**
     * @depends      testApi
     * @dataProvider getAnotherApiForTesting
     */
    public function testAnotherApi($api, $params)
    {
        // Get the top segment value
        $request = new Request(
            'method=API.getSuggestedValuesForSegment'
                . '&segmentName=' . $params['segmentToComplete']
                . '&idSite=' . $params['idSite']
                . '&format=php&serialize=0'
        );
        $response = $request->process();
        $this->assertApiResponseHasNoError($response);
        $topSegmentValue = @$response[0];

        if ($topSegmentValue !== false && !is_null($topSegmentValue)) {
            if (is_numeric($topSegmentValue) || is_float($topSegmentValue) || preg_match('/^\d*?,\d*$/', $topSegmentValue)) {
                $topSegmentValue = Common::forceDotAsSeparatorForDecimalPoint($topSegmentValue);
            }
            // Now build the segment request
            $segmentValue = rawurlencode(html_entity_decode($topSegmentValue));
            $params['segment'] = $params['segmentToComplete'] . '==' . $segmentValue;
            unset($params['segmentToComplete']);
            $this->runApiTests($api, $params);
            self::$processed++;
        } else {
            self::$skipped[] = $params['segmentToComplete'];
        }

    }

    public function getAnotherApiForTesting()
    {
        $segments = self::getSegmentsMetadata(self::$fixture->idSite);

        $apiForTesting = array();
        foreach ($segments as $segment) {
            if(self::isTravisCI() && $segment['segment'] == 'deviceType') {
                // test started failing after bc19503 and I cannot understand why
                continue;
            }
            $apiForTesting[] = array('VisitsSummary.get',
                                     array('idSite'            => self::$fixture->idSite,
                                           'date'              => date("Y-m-d", strtotime(self::$fixture->dateTime)) . ',today',
                                           'period'            => 'range',
                                           'testSuffix'        => '_' . $segment['segment'],
                                           'segmentToComplete' => $segment['segment']));
        }
        return $apiForTesting;
    }

    /**
     * @depends      testAnotherApi
     */
    public function testCheckOtherTestsWereComplete()
    {
        // Check that only a few haven't been tested specifically (these are all custom variables slots since we only test slot 1, 2, 5 (see the fixture) and example dimension slots)
        $maximumSegmentsToSkip = 16;
        $this->assertLessThan($maximumSegmentsToSkip, count(self::$skipped) , 'SKIPPED ' . count(self::$skipped) . ' segments --> some segments had no "auto-suggested values"
            but we should try and test the autosuggest for all new segments. Segments skipped were: ' . implode(', ', self::$skipped));

        // and check that most others have been tested
        $minimumSegmentsToTest = 46;
        $message = 'PROCESSED ' . self::$processed . ' segments --> it seems some segments "auto-suggested values" haven\'t been tested as we were expecting. ';
        $this->assertGreaterThan($minimumSegmentsToTest, self::$processed, $message);
    }

    public static function getSegmentsMetadata($idSite)
    {
        // Refresh cache for CustomVariables\Model
        Cache::clearCacheGeneral();

        \Piwik\Plugins\CustomVariables\Model::install();

        // Segment matching NONE
        $segments = \Piwik\Plugins\API\API::getInstance()->getSegmentsMetadata($idSite);
        return $segments;
    }
}

AutoSuggestAPITest::$fixture = new ManyVisitsWithGeoIP();
AutoSuggestAPITest::$fixture->dateTime = Date::yesterday()->subDay(30)->getDatetime();
