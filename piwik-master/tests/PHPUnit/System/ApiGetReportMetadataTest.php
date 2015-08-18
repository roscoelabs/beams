<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Tests\System;

use Piwik\API\Proxy;
use Piwik\Tests\Framework\TestCase\SystemTestCase;
use Piwik\Tests\Fixtures\ThreeGoalsOnePageview;

/**
 * This tests the output of the API plugin API
 * It will return metadata about all API reports from all plugins
 * as well as the data itself, pre-processed and ready to be displayed
 *
 * @group Plugins
 * @group ApiGetReportMetadataTest
 */
class ApiGetReportMetadataTest extends SystemTestCase
{
    public static $fixture = null; // initialized below class definition

    public function setUp()
    {
        parent::setUp();

        // From Piwik 1.5, we hide Goals.getConversions and other get* methods via @ignore, but we
        // ensure that they still work. This hack allows the API proxy to let us generate example
        // URLs for the ignored functions
        Proxy::getInstance()->setHideIgnoredFunctions(false);
    }

    public function tearDown()
    {
        parent::tearDown();

        // reset that value after the test
        Proxy::getInstance()->setHideIgnoredFunctions(true);
    }

    public static function getOutputPrefix()
    {
        return 'apiGetReportMetadata';
    }

    public function getApiForTesting()
    {
        $idSite = self::$fixture->idSite;
        $dateTime = self::$fixture->dateTime;

        return array(
            array('API', array('idSite' => $idSite, 'date' => $dateTime)),

            // test w/ hideMetricsDocs=true
            array('API.getMetadata', array('idSite'                 => $idSite, 'date' => $dateTime,
                                           'apiModule'              => 'Actions', 'apiAction' => 'get',
                                           'testSuffix'             => '_hideMetricsDoc',
                                           'otherRequestParameters' => array('hideMetricsDoc' => 1))),
            array('API.getProcessedReport', array('idSite'                 => $idSite, 'date' => $dateTime,
                                                  'apiModule'              => 'Actions', 'apiAction' => 'get',
                                                  'testSuffix'             => '_hideMetricsDoc',
                                                  'otherRequestParameters' => array('hideMetricsDoc' => 1))),

            // Test w/ showRawMetrics=true
            array('API.getProcessedReport', array('idSite'                 => $idSite, 'date' => $dateTime,
                                                  'apiModule'              => 'UserCountry', 'apiAction' => 'getCountry',
                                                  'testSuffix'             => '_showRawMetrics',
                                                  'otherRequestParameters' => array('showRawMetrics' => 1))),

            // Test w/ showRawMetrics=true
            array('Actions.getPageTitles', array('idSite'     => $idSite, 'date' => $dateTime,
                                                 'testSuffix' => '_pageTitleZeroString')),

            // test php renderer w/ array data
            array('API.getDefaultMetricTranslations', array('idSite' => $idSite, 'date' => $dateTime,
                                                            'format' => 'php', 'testSuffix' => '_phpRenderer')),
        );
    }

    /**
     * @dataProvider getApiForTesting
     */
    public function testApi($api, $params)
    {
        $this->runApiTests($api, $params);
    }
}

ApiGetReportMetadataTest::$fixture = new ThreeGoalsOnePageview();