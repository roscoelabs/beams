<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Tests\Fixtures;

use Piwik\Date;
use Piwik\Tests\Framework\Fixture;

/**
 * Adds one site and tracks one visit with several pageviews.
 */
class OneVisitSeveralPageViews extends Fixture
{
    public $dateTime = '2010-03-06 11:22:33';
    public $idSite = 1;

    public function setUp()
    {
        $this->setUpWebsitesAndGoals();
        $this->trackVisits();
    }

    public function tearDown()
    {
        // empty
    }

    private function setUpWebsitesAndGoals()
    {
        if (!self::siteCreated($idSite = 1)) {
            self::createWebsite($this->dateTime);
        }
    }

    private function trackVisits()
    {
        $dateTime = $this->dateTime;
        $idSite = $this->idSite;
        $t = self::getTracker($idSite, $dateTime, $defaultInit = true);

        $t->setUrlReferrer('http://www.google.com.vn/url?sa=t&rct=j&q=%3C%3E%26%5C%22the%20pdo%20extension%20is%20required%20for%20this%20adapter%20but%20the%20extension%20is%20not%20loaded&source=web&cd=4&ved=0FjAD&url=http%3A%2F%2Fforum.piwik.org%2Fread.php%3F2%2C1011&ei=y-HHAQ&usg=AFQjCN2-nt5_GgDeg&cad=rja');
        $t->setUrl('http://example.org/%C3%A9%C3%A9%C3%A9%22%27...%20%3Cthis%20is%20cool%3E!');
        $t->setGenerationTime(523);
        self::checkResponse($t->doTrackPageView('incredible title! <>,;'));

        $t->setUrl('http://example.org/dir/file.php?foo=bar&foo2=bar');
        $t->setForceVisitDateTime(Date::factory($dateTime)->addHour(0.2)->getDatetime());
        $t->setGenerationTime(123);
        self::checkResponse($t->doTrackPageView('incredible title! <>,;'));

        $t->setUrl('http://example.org/dir/file.php?foo=bar&foo2=bar2');
        $t->setForceVisitDateTime(Date::factory($dateTime)->addHour(0.3)->getDatetime());
        $t->setGenerationTime(153);
        self::checkResponse($t->doTrackPageView('incredible parent title! <>,; / subtitle <>,;'));

        $t->setUrl('http://example.org/dir/file.php?foo=bar&foo2=bar2');
        $t->setForceVisitDateTime(Date::factory($dateTime)->addHour(0.31)->getDatetime());
        $t->setGenerationTime(153);
        self::checkResponse($t->doTrackEvent('Category', 'Action', 'Name', 11111));

        $t->setUrl('http://example.org/dir2/file.php?foo=bar&foo2=bar');
        $t->setForceVisitDateTime(Date::factory($dateTime)->addHour(0.4)->getDatetime());
        $t->setGenerationTime(1233);
        self::checkResponse($t->doTrackPageView('incredible title! <>,;'));

        $t->setUrl('http://example.org/dir2/sub/0/file.php');
        $t->setForceVisitDateTime(Date::factory($dateTime)->addHour(0.4)->getDatetime());
        // Very high Generation time should be ignored
        $t->setGenerationTime(6350000);
        self::checkResponse($t->doTrackPageView('incredible title! <>,;'));

        // visit terminal & branch pages w/ the same name so we can test the ! label filter query operator
        $t->setUrl('http://example.org/dir/subdir/');
        $t->setForceVisitDateTime(Date::factory($dateTime)->addHour(0.41)->getDatetime());
        $t->setGenerationTime(233);
        self::checkResponse($t->doTrackPageView('check <> / @one@ / two'));

        $t->setUrl('http://example.org/dir/subdir');
        $t->setForceVisitDateTime(Date::factory($dateTime)->addHour(0.42)->getDatetime());
        $t->setGenerationTime(333);
        self::checkResponse($t->doTrackPageView('check <> / @one@'));

        $t->setUrl('http://example.org/0');
        $t->setForceVisitDateTime(Date::factory($dateTime)->addHour(0.4)->getDatetime());
        $t->setGenerationTime(635);
        self::checkResponse($t->doTrackPageView('I am URL zero!'));
    }
}