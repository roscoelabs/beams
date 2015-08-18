<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link    http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\Contents\tests\System;

use Piwik\Tests\Framework\TestCase\SystemTestCase;
use Piwik\Plugins\Contents\tests\Fixtures\TwoVisitsWithContents;
use Piwik\Translate;

/**
 * Testing Contents
 *
 * @group ContentsTest
 * @group System
 * @group Plugins
 */
class ContentsTest extends SystemTestCase
{
    public static $fixture = null; // initialized below class definition

    /**
     * @dataProvider getApiForTesting
     */
    public function testApi($api, $params)
    {
        if(self::isMysqli()) {
            $this->markTestSkipped('Sometimes fail on MYSQLI (at random)');
        }
        $this->runApiTests($api, $params);
    }

    protected function getApiToCall()
    {
        return array(
            'Contents.getContentNames',
            'Contents.getContentPieces',
            'Actions.get',
            'Actions.getPageUrls',
            'Live.getLastVisitsDetails'
        );
    }

    protected function setup()
    {
        parent::setup();
        Translate::loadAllTranslations();
    }

    protected function tearDown()
    {
        parent::tearDown();
        Translate::reset();
    }

    public function getApiForTesting()
    {
        $dateTime = self::$fixture->dateTime;
        $idSite1 = self::$fixture->idSite;

        $apiToCallProcessedReportMetadata = $this->getApiToCall();

        $dayPeriod = 'day';
        $periods = array($dayPeriod, 'month');

        $apisToTest = array('Contents', 'Actions.getPageUrls', 'Live.getLastVisitsDetails');
        $result = array(
            array($apiToCallProcessedReportMetadata, array(
                'idSite'       => $idSite1,
                'date'         => $dateTime,
                'periods'      => $periods,
                'setDateLastN' => false,
                'testSuffix'   => '')),

            array($apisToTest, array(
                'idSite'       => $idSite1,
                'date'         => $dateTime,
                'periods'      => $dayPeriod,
                'segment'      => "contentName==ImageAd,contentPiece==".urlencode('Click to download Piwik now'),
                'setDateLastN' => false,
                'testSuffix'   => 'contentNameOrPieceMatch')
            ),

            array($apisToTest, array(
                'idSite'       => $idSite1,
                'date'         => $dateTime,
                'periods'      => $dayPeriod,
                'segment'      => "contentTarget==".urlencode('http://www.example.com'),
                'setDateLastN' => false,
                'testSuffix'   => '_contentTargetMatch')
            ),

            array($apisToTest, array(
                'idSite'       => $idSite1,
                'date'         => $dateTime,
                'periods'      => $dayPeriod,
                'segment'      => "contentInteraction==click",
                'setDateLastN' => false,
                'testSuffix'   => '_contentInteractionMatch')
            )
        );

        $apiToCallProcessedReportMetadata = array(
            'Contents.getContentNames',
            'Contents.getContentPieces'
        );
        // testing metadata API for Contents reports
        foreach ($apiToCallProcessedReportMetadata as $api) {
            list($apiModule, $apiAction) = explode(".", $api);

            $result[] = array(
                'API.getProcessedReport', array('idSite'       => $idSite1,
                                                'date'         => $dateTime,
                                                'periods'      => $dayPeriod,
                                                'setDateLastN' => true,
                                                'apiModule'    => $apiModule,
                                                'apiAction'    => $apiAction,
                                                'testSuffix'   => '_' . $api . '_lastN')
            );
        }

        return $result;
    }

    public static function getOutputPrefix()
    {
        return 'Contents';
    }

    public static function getPathToTestDirectory()
    {
        return dirname(__FILE__);
    }
}

ContentsTest::$fixture = new TwoVisitsWithContents();