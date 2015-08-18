<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CoreHome\tests\Integration\Tracker;

use Piwik\Cache;
use Piwik\CacheId;
use Piwik\Date;
use Piwik\Plugins\CoreHome\Tracker\VisitRequestProcessor;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Plugins\SitesManager\API;
use Piwik\Tracker\Request;
use Piwik\Tracker\Visit;
use Piwik\Tracker\Visit\VisitProperties;

/**
 * @group CoreHome
 * @group CoreHome_Integration
 */
class VisitRequestProcessorTest extends IntegrationTestCase
{
    public function test_isVisitNew_ReturnsFalse_IfLastActionTimestampIsWithinVisitTimeLength_AndNoDimensionForcesVisit_AndVisitorKnown()
    {
        $this->setDimensionsWithOnNewVisit(array(false, false, false));

        /** @var VisitRequestProcessor $visit */
        list($visit, $visitProperties, $request) = $this->makeVisitorAndAction(
            $lastActionTime = '2012-01-02 08:08:34', $thisActionTime = '2012-01-02 08:12:45', $isVisitorKnown = true);

        $result = $visit->isVisitNew($visitProperties, $request);

        $this->assertFalse($result);
    }

    public function test_isVisitNew_ReturnsTrue_IfLastActionTimestampWasYesterday()
    {
        $this->setDimensionsWithOnNewVisit(array(false, false, false));

        // test same day
        /** @var VisitRequestProcessor $visit */
        list($visit, $visitProperties, $request) = $this->makeVisitorAndAction(
            $lastActionTime = '2012-01-01 23:59:58', $thisActionTime = '2012-01-01 23:59:59', $isVisitorKnown = true);
        $result = $visit->isVisitNew($visitProperties, $request);
        $this->assertFalse($result);

        // test different day
        list($visit, $visitProperties, $request) = $this->makeVisitorAndAction(
            $lastActionTime = '2012-01-01 23:59:58', $thisActionTime = '2012-01-02 00:00:01', $isVisitorKnown = true);
        $result = $visit->isVisitNew($visitProperties, $request);
        $this->assertTrue($result);
    }


    public function test_isVisitNew_ReturnsTrue_IfLastActionTimestampIsNotWithinVisitTimeLength_AndNoDimensionForcesVisit_AndVisitorNotKnown()
    {
        $this->setDimensionsWithOnNewVisit(array(false, false, false));

        /** @var VisitRequestProcessor $visit */
        list($visit, $visitProperties, $request) = $this->makeVisitorAndAction($lastActionTime = '2012-01-02 08:08:34', $thisActionTime = '2012-01-02 09:12:45');

        $result = $visit->isVisitNew($visitProperties, $request);

        $this->assertTrue($result);
    }

    public function test_isVisitNew_ReturnsTrue_IfLastActionTimestampIsWithinVisitTimeLength_AndDimensionForcesVisit()
    {
        $this->setDimensionsWithOnNewVisit(array(false, false, true));

        /** @var VisitRequestProcessor $visit */
        list($visit, $visitProperties, $request) = $this->makeVisitorAndAction($lastActionTime = '2012-01-02 08:08:34', $thisActionTime = '2012-01-02 08:12:45');

        $result = $visit->isVisitNew($visitProperties, $request);

        $this->assertTrue($result);
    }

    public function test_isVisitNew_ReturnsTrue_IfDimensionForcesVisit_AndVisitorKnown()
    {
        $this->setDimensionsWithOnNewVisit(array(false, false, true));

        /** @var VisitRequestProcessor $visit */
        list($visit, $visitProperties, $request) = $this->makeVisitorAndAction($lastActionTime = '2012-01-02 08:08:34', $thisActionTime = '2012-01-02 08:12:45');

        $result = $visit->isVisitNew($visitProperties, $request);

        $this->assertTrue($result);
    }

    private function makeVisitorAndAction($lastActionTimestamp, $currentActionTime, $isVisitorKnown = false)
    {
        $idsite = API::getInstance()->addSite("name", "http://piwik.net/");

        /** @var Request $request */
        list($visit, $request) = $this->prepareVisitWithRequest(array('idsite' => $idsite), $currentActionTime);

        $visitProperties = new VisitProperties();
        $visitProperties->setProperty('visit_last_action_time', Date::factory($lastActionTimestamp)->getTimestamp());
        $request->setMetadata('CoreHome', 'isVisitorKnown', $isVisitorKnown);

        return array($visit, $visitProperties, $request);
    }

    private function setDimensionsWithOnNewVisit($dimensionOnNewVisitResults)
    {
        $dimensions = array();
        foreach ($dimensionOnNewVisitResults as $onNewVisitResult) {
            $dim = $this->getMock('Piwik\\Plugin\\Dimension', array('shouldForceNewVisit', 'getColumnName'));
            $dim->expects($this->any())->method('shouldForceNewVisit')->will($this->returnValue($onNewVisitResult));
            $dimensions[] = $dim;
        }

        $cache = Cache::getTransientCache();
        $cache->save(CacheId::pluginAware('VisitDimensions'), $dimensions);
        Visit::$dimensions = null;
    }

    private function prepareVisitWithRequest($requestParams, $requestDate)
    {
        $request = new Request($requestParams);
        $request->setCurrentTimestamp(Date::factory($requestDate)->getTimestamp());

        $visit = self::$fixture->piwikEnvironment->getContainer()->get('Piwik\Plugins\CoreHome\Tracker\VisitRequestProcessor');

        return array($visit, $request);
    }
}
