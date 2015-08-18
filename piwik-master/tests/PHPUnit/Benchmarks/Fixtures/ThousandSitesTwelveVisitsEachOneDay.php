<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
use Piwik\Date;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\BenchmarkTestCase;

/**
 * Reusable fixture. Tracks twelve thousand page views for 1000 sites on one day.
 */
class Piwik_Test_Fixture_ThousandSitesTwelveVisitsEachOneDay
{
    public $date = '2010-01-01';
    public $period = 'day';
    public $idSite = 'all';

    public function setUp()
    {
        // add one thousand sites
        $allIdSites = array();
        for ($i = 0; $i < 1000; ++$i) {
            $allIdSites[] = Fixture::createWebsite($this->date, $ecommerce = 1, $siteName = "Site #$i");
        }

        $urls = array();
        for ($i = 0; $i != 3; ++$i) {
            $url = "http://whatever.com/" . ($i - 1) . "/" . ($i + 1);
            $title = "page view " . ($i - 1) . " / " . ($i + 1);
            $urls[$url] = $title;
        }

        $visitTimes = array();
        $date = Date::factory($this->date);
        for ($i = 0; $i != 4; ++$i) {
            $visitTimes[] = $date->addHour($i)->getDatetime();
        }

        // add 12000 visits (3 visitors with 4 visits each for each site) w/ 3 pageviews each on one day
        foreach ($visitTimes as $visitTime) {
            foreach ($allIdSites as $idSite) {
                for ($visitor = 0; $visitor != 3; ++$visitor) {
                    $t = BenchmarkTestCase::getLocalTracker($idSite);

                    $ip = "157.5.6." . ($visitor + 1);
                    $t->setIp($ip);
                    $t->setNewVisitorId();

                    $t->setForceVisitDateTime($visitTime);
                    foreach ($urls as $url => $title) {
                        $t->setUrl($url);
                        $t->doTrackPageView($title);
                    }
                }
            }
        }
    }
}

