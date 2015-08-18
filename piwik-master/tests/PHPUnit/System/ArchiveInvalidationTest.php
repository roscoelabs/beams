<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link    http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Tests\System;

use Piwik\API\Request;
use Piwik\Config;
use Piwik\Date;
use Piwik\Tests\Fixtures\VisitsTwoWebsitesWithAdditionalVisits;
use Piwik\Tests\Framework\TestCase\SystemTestCase;

/**
 * Track visits before website creation date and test that Piwik handles them correctly.
 *
 * This tests that the API method invalidateArchivedReports works correctly, that it deletes data:
 * - on one or multiple websites
 * - for a given set of dates (and optional period)
 *
 * @group Core
 * @group ArchiveInvalidationTest
 */
class ArchiveInvalidationTest extends SystemTestCase
{
    /**
     * @var VisitsTwoWebsitesWithAdditionalVisits
     */
    public static $fixture = null; // initialized below class definition

    protected $suffix = '_NewDataShouldNotAppear';

    /**
     * @dataProvider getApiForTesting
     */
    public function testApi($api, $params)
    {
        $this->runApiTests($api, $params);
    }

    /**
     * This should NOT return data for old dates before website creation
     */
    public function getApiForTesting()
    {
        // We test a typical Numeric and a Recursive blob reports
        $apiToCall = array('VisitsSummary.get', 'Actions.getPageUrls');

        // Build tests for the 2 websites
        return array(

            array($apiToCall, array('idSite'                 => self::$fixture->idSite2,
                                    'testSuffix'             => 'Website' . self::$fixture->idSite2 . "_NewDataShouldNotAppear_BecauseWeekWasNotInvalidated",
                                    'date'                   => self::$fixture->dateTimeFirstDateWebsite2,
                                    'periods'                => 'week',
                                    'segment'                => 'pageUrl=@category/',
                                    'setDateLastN'           => 4, // 4months ahead
                                    'otherRequestParameters' => array('expanded' => 1))
            ),
            array($apiToCall, array('idSite'                 => self::$fixture->idSite1,
                                    'testSuffix'             => 'Website' . self::$fixture->idSite1 . $this->suffix,
                                    'date'                   => self::$fixture->dateTimeFirstDateWebsite1,
                                    'periods'                => 'month',
                                    'setDateLastN'           => 4, // 4months ahead
                                    'otherRequestParameters' => array('expanded' => 1))
            ),

            array($apiToCall, array('idSite'                 => self::$fixture->idSite2,
                                    'testSuffix'             => 'Website' . self::$fixture->idSite2 . $this->suffix,
                                    'date'                   => self::$fixture->dateTimeFirstDateWebsite2,
                                    'periods'                => 'month',
                                    'segment'                => 'pageUrl=@category/',
                                    'setDateLastN'           => 4, // 4months ahead
                                    'otherRequestParameters' => array('expanded' => 1))
            )
        );
    }

    /**
     * @depends      testApi
     * @dataProvider getApiForTesting
     */
    public function testSameApi($api, $params)
    {
        $this->setBrowserArchivingTriggering(0);
        self::$fixture->trackMoreVisits($params['idSite']);

        $this->invalidateTestArchives();
        $this->runApiTests($api, $params);
    }

    /**
     * @depends      testApi
     * @depends      testSameApi
     * @dataProvider getAnotherApiForTesting
     */
    public function testAnotherApi($api, $params)
    {
        if ($params['periods'] === 'month') {
            // we do now need to invalidate weeks as well since months are based on weeks
            $this->invalidateTestArchive(self::$fixture->idSite2, 'week', self::$fixture->dateTimeFirstDateWebsite2);
        }

        $this->setBrowserArchivingTriggering(1);

        $this->runApiTests($api, $params);
    }

    /**
     * This is called after getApiToTest()
     * We invalidate old reports and check that data is now returned for old dates
     */
    public function getAnotherApiForTesting()
    {
        $this->suffix = '_NewDataShouldAppear';
        return $this->getApiForTesting();
    }

    public static function getOutputPrefix()
    {
        return 'Archive_Invalidation';
    }

    protected function setBrowserArchivingTriggering($value)
    {
        Config::getInstance()->General['enable_browser_archiving_triggering'] = $value;
    }

    protected function invalidateTestArchives()
    {
        $dateToInvalidate1 = new \DateTime(self::$fixture->dateTimeFirstDateWebsite1);

        $r = new Request("module=API&method=CoreAdminHome.invalidateArchivedReports&idSites=" . self::$fixture->idSite1 . "&dates=" . $dateToInvalidate1->format('Y-m-d'));
        $this->assertApiResponseHasNoError($r->process());

        // Days & Months reports only are invalidated and we test our weekly report will still show old data.
        $this->invalidateTestArchive(self::$fixture->idSite2, 'day', self::$fixture->dateTimeFirstDateWebsite2);
        $this->invalidateTestArchive(self::$fixture->idSite2, 'month', self::$fixture->dateTimeFirstDateWebsite2);
    }

    private function invalidateTestArchive($idSite, $period, $dateTime)
    {
        $dates = new \DateTime($dateTime);
        $dates = $dates->format('Y-m-d');
        $r = new Request("module=API&method=CoreAdminHome.invalidateArchivedReports&period=$period&idSites=$idSite&dates=$dates");
        $this->assertApiResponseHasNoError($r->process());
    }
}

ArchiveInvalidationTest::$fixture = new VisitsTwoWebsitesWithAdditionalVisits();