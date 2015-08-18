<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Integration\Tracker;

use Piwik\Cache;
use Piwik\Archive\ArchiveInvalidator;
use Piwik\Date;
use Piwik\Network\IPUtils;
use Piwik\Plugin\Manager;
use Piwik\Plugins\SitesManager\API;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tracker\Request;
use Piwik\Tracker\Visit;
use Piwik\Tracker\VisitExcluded;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group Core
 */
class VisitTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();

        // setup the access layer
        FakeAccess::$superUser = true;

        Manager::getInstance()->loadTrackerPlugins();
        Manager::getInstance()->loadPlugin('SitesManager');
        Visit::$dimensions = null;
    }

    /**
     * Dataprovider
     */
    public function getExcludedIpTestData()
    {
        return array(
            array('12.12.12.12', array(
                '12.12.12.12'     => true,
                '12.12.12.11'     => false,
                '12.12.12.13'     => false,
                '0.0.0.0'         => false,
                '255.255.255.255' => false
            )),
            array('12.12.12.12/32', array(
                '12.12.12.12'     => true,
                '12.12.12.11'     => false,
                '12.12.12.13'     => false,
                '0.0.0.0'         => false,
                '255.255.255.255' => false
            )),
            array('12.12.12.*', array(
                '12.12.12.0'      => true,
                '12.12.12.255'    => true,
                '12.12.12.12'     => true,
                '12.12.11.255'    => false,
                '12.12.13.0'      => false,
                '0.0.0.0'         => false,
                '255.255.255.255' => false,
            )),
            array('12.12.12.0/24', array(
                '12.12.12.0'      => true,
                '12.12.12.255'    => true,
                '12.12.12.12'     => true,
                '12.12.11.255'    => false,
                '12.12.13.0'      => false,
                '0.0.0.0'         => false,
                '255.255.255.255' => false,
            )),
            // add some ipv6 addresses!
        );
    }

    /**
     * @dataProvider getExcludedIpTestData
     */
    public function testIsVisitorIpExcluded($excludedIp, $tests)
    {
        $idsite = API::getInstance()->addSite("name", "http://piwik.net/", $ecommerce = 0,
            $siteSearch = 1, $searchKeywordParameters = null, $searchCategoryParameters = null, $excludedIp);

        $request = new Request(array('idsite' => $idsite));

        // test that IPs within the range, or the given IP, are excluded
        foreach ($tests as $ip => $expected) {
            $testIpIsExcluded = IPUtils::stringToBinaryIP($ip);

            $excluded = new VisitExcluded_public($request, $testIpIsExcluded);
            $this->assertSame($expected, $excluded->public_isVisitorIpExcluded($testIpIsExcluded));
        }
    }

    /**
     * @dataProvider getChromeDataSaverData
     */
    public function testVisitShouldNotBeExcluded_IfMadeViaChromeDataSaverCompressionProxy($ip, $isNonHumanBot)
    {
        $idsite = API::getInstance()->addSite("name", "http://piwik.net/", $ecommerce = 0,
            $siteSearch = 1, $searchKeywordParameters = null, $searchCategoryParameters = null);

        $request = new Request(array('idsite' => $idsite));

        $testIpIsExcluded = IPUtils::stringToBinaryIP($ip);

        $_SERVER['HTTP_VIA'] = '1.1 Chrome-Compression-Proxy';
        $excluded = new VisitExcluded_public($request, $testIpIsExcluded);
        $isBot = $excluded->public_isNonHumanBot($testIpIsExcluded);
        unset($_SERVER['HTTP_VIA']);
        $this->assertSame($isNonHumanBot, $isBot);
    }

    public function getChromeDataSaverData()
    {
        return array(
            array('216.239.32.0', $isNonHumanBot = false), // false because google ips
            array('66.249.93.251', $isNonHumanBot = false),
            array('173.194.0.1', $isNonHumanBot = false),
            array('72.30.198.1', $isNonHumanBot = true), // not a google bot, a yahoo bot
            array('64.4.0.1', $isNonHumanBot = true), // a MSN bot
        );
    }

    /**
     * Dataprovider for testIsVisitorUserAgentExcluded.
     */
    public function getExcludedUserAgentTestData()
    {
        return array(
            array('', array(
                'whatever'        => false,
                ''                => false,
                'nlksdjfsldkjfsa' => false,
            )),
            array('mozilla', array(
                'this has mozilla in it' => true,
                'this doesn\'t'          => false,
                'partial presence: mozi' => false,
            )),
            array('cHrOmE,notinthere,&^%', array(
                'chrome is here' => true,
                'CHROME is here' => true,
                '12&^%345'       => true,
                'sfasdf'         => false,
            )),
        );
    }

    /**
     * @dataProvider getExcludedUserAgentTestData
     */
    public function testIsVisitorUserAgentExcluded($excludedUserAgent, $tests)
    {
        API::getInstance()->setSiteSpecificUserAgentExcludeEnabled(true);

        $idsite = API::getInstance()->addSite("name", "http://piwik.net/", $ecommerce = 0,
            $siteSearch = 1, $searchKeywordParameters = null, $searchCategoryParameters = null, $excludedIp = null,
            $excludedQueryParameters = null, $timezone = null, $currency = null, $group = null, $startDate = null,
            $excludedUserAgent);

        $request = new Request(array('idsite' => $idsite));

        // test that user agents that contain excluded user agent strings are excluded
        foreach ($tests as $ua => $expected) {
            $excluded = new VisitExcluded_public($request, $ip = false, $ua);

            $this->assertSame($expected, $excluded->public_isUserAgentExcluded(), "Result if isUserAgentExcluded('$ua') was not " . ($expected ? 'true' : 'false') . ".");
        }
    }

    /**
     * @group referrerIsKnownSpam
     */
    public function testIsVisitor_referrerIsKnownSpam()
    {
        $knownSpammers = array(
            'http://semalt.com' => true,
            'http://semalt.com/random/sub/page' => true,
            'http://semalt.com/out/of/here?mate' => true,
            'http://buttons-for-website.com/out/of/here?mate' => true,
            'https://buttons-for-website.com' => true,
            'https://make-money-online.7makemoneyonline.com' => true,
            'https://7makemoneyonline.com' => true,
            'http://valid.domain/' => false,
            'http://valid.domain/page' => false,
            'https://valid.domain/page' => false,
        );
        API::getInstance()->setSiteSpecificUserAgentExcludeEnabled(true);

        $idsite = API::getInstance()->addSite("name", "http://piwik.net/");

        // test that user agents that contain excluded user agent strings are excluded
        foreach ($knownSpammers as $spamUrl => $expectedIsReferrerSpam) {
            $spamUrl = urlencode($spamUrl);
            $request = new Request(array(
                'idsite' => $idsite,
                'urlref' => $spamUrl
            ));
            $excluded = new VisitExcluded_public($request);

            $this->assertSame($expectedIsReferrerSpam, $excluded->public_isReferrerSpamExcluded(), $spamUrl);
        }
    }

    /**
     * @group IpIsKnownBot
     */
    public function testIsVisitor_ipIsKnownBot()
    {
        $isIpBot = array(
            // Source: http://forum.piwik.org/read.php?3,108926
            '66.249.85.36' => true,
            '66.249.91.150' => true,
            '64.233.172.1' => true,
            '64.233.172.200' => true,
            '66.249.88.216' => true,
            '66.249.83.204' => true,
            '64.233.172.6' => true,

            // ddos bot
            '1.202.218.8' => true,

            // Not bots
            '66.248.91.150' => false,
            '66.250.91.150' => false,
            // almost google range but not google
            '66.249.2.1' => false,
            '66.249.60.1' => false,
        );

        $idsite = API::getInstance()->addSite("name", "http://piwik.net/");
        $request = new Request(array('idsite' => $idsite, 'bots' => 0));

        foreach ($isIpBot as $ip => $isBot) {
            $excluded = new VisitExcluded_public($request, IPUtils::stringToBinaryIP($ip));

            $this->assertSame($isBot, $excluded->public_isNonHumanBot(), $ip);
        }
    }

    /**
     * @group UserAgentIsKnownBot
     */
    public function testIsVisitor_userAgentIsKnownBot()
    {
        $isUserAgentBot = array(
            'baiduspider' => true,
            'bingbot' => true,
            'BINGBOT' => true,
            'x BingBot x' => true,
            'BingPreview' => true,
            'facebookexternalhit' => true,
            'YottaaMonitor' => true,
            'Mozilla/5.0 (compatible; CloudFlare-AlwaysOnline/1.0; +http://www.cloudflare.com/always-online) XXXX' => true,
            'Pingdom.com_bot_version_1.4_(http://www.pingdom.com/)' => true,
            'Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)' => true,
            'Exabot/2.0' => true,
            'sogou spider' => true,
            'Mozilla/5.0(compatible;Sosospider/2.0;+http://help.soso.com/webspider.htm)' => true,

            'AdsBot-Google (+http://www.google.com/adsbot.html)' => true,
            'Google Page Speed Insights' => true,
            // Web snippets
            'Mozilla/5.0 (Windows NT 6.1; rv:6.0) Gecko/20110814 Firefox/6.0 Google (+https://developers.google.com/+/web/snippet/)' => true,
            // Google Web Preview
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/534.51 (KHTML, like Gecko; Google Web Preview) Chrome/12.0.742 Safari/534.51' => true,
            'Googlebot-Video/1.0' => true,
            'Googlebot' => true,

            'random' => false,
            'hello world' => false,
            'this is a user agent' => false,
            'Mozilla' => false,
        );

        $idsite = API::getInstance()->addSite("name", "http://piwik.net/");

        foreach ($isUserAgentBot as $userAgent => $isBot) {
            $request = new Request(array(
                'idsite' => $idsite,
                'bots' => 0,
                'ua' => $userAgent,
            ));

            $excluded = new VisitExcluded_public($request);

            $this->assertSame($isBot, $excluded->public_isNonHumanBot(), $userAgent);
        }
    }

    public function test_markArchivedReportsAsInvalidIfArchiveAlreadyFinished_ShouldRemember_IfRequestWasDoneLongAgo()
    {
        $currentActionTime = '2012-01-02 08:12:45';
        $idsite = API::getInstance()->addSite('name', 'http://piwik.net/');

        $expectedRemembered = array('2012-01-02' => array($idsite));

        $this->assertRememberedArchivedReportsThatShouldBeInvalidated($idsite, $currentActionTime, $expectedRemembered);
    }

    public function test_markArchivedReportsAsInvalidIfArchiveAlreadyFinished_ShouldNotRemember_IfRequestWasDoneJustAtStartOfTheDay()
    {
        $currentActionTime = Date::today()->getDatetime();
        $idsite = API::getInstance()->addSite('name', 'http://piwik.net/');

        $expectedRemembered = array();

        $this->assertRememberedArchivedReportsThatShouldBeInvalidated($idsite, $currentActionTime, $expectedRemembered);
    }

    public function test_markArchivedReportsAsInvalidIfArchiveAlreadyFinished_ShouldRemember_IfRequestWasDoneAt11PMTheDayBefore()
    {
        $currentActionTime = Date::today()->subHour(1)->getDatetime();
        $idsite = API::getInstance()->addSite('name', 'http://piwik.net/');

        $expectedRemembered = array(
            substr($currentActionTime, 0, 10) => array($idsite)
        );

        $this->assertRememberedArchivedReportsThatShouldBeInvalidated($idsite, $currentActionTime, $expectedRemembered);
    }

    public function test_markArchivedReportsAsInvalidIfArchiveAlreadyFinished_shouldConsiderWebsitesTimezone()
    {
        $timezone1 = 'UTC+4';
        $timezone2 = 'UTC+6';

        $currentActionTime1 = Date::today()->setTimezone($timezone1)->getDatetime();
        $currentActionTime2 = Date::today()->setTimezone($timezone2)->getDatetime();
        $idsite = API::getInstance()->addSite('name', 'http://piwik.net/', $ecommerce = null,
            $siteSearch = null,
            $searchKeywordParameters = null,
            $searchCategoryParameters = null,
            $excludedIps = null,
            $excludedQueryParameters = null,
            $timezone = 'UTC+5');

        $expectedRemembered = array(
            substr($currentActionTime1, 0, 10) => array($idsite)
        );

        // if website timezone was von considered both would be today (expected = array())
        $this->assertRememberedArchivedReportsThatShouldBeInvalidated($idsite, $currentActionTime1, array());
        $this->assertRememberedArchivedReportsThatShouldBeInvalidated($idsite, $currentActionTime2, $expectedRemembered);
    }

    private function assertRememberedArchivedReportsThatShouldBeInvalidated($idsite, $requestDate, $expectedRemeberedArchivedReports)
    {
        /** @var Visit $visit */
        list($visit) = $this->prepareVisitWithRequest(array(
            'idsite' => $idsite,
            'rec' => 1,
            'cip' => '156.146.156.146',
            'token_auth' => Fixture::getTokenAuth()
        ), $requestDate);

        $visit->handle();

        $archive = new ArchiveInvalidator();
        $remembered = $archive->getRememberedArchivedReportsThatShouldBeInvalidated();

        $this->assertSame($expectedRemeberedArchivedReports, $remembered);
    }

    private function prepareVisitWithRequest($requestParams, $requestDate)
    {
        $request = new Request($requestParams);
        $request->setCurrentTimestamp(Date::factory($requestDate)->getTimestamp());

        $visit = new Visit();
        $visit->setRequest($request);

        $visit->handle();

        return array($visit, $request);
    }

    public function provideContainerConfig()
    {
        return array(
            'Piwik\Access' => new FakeAccess()
        );
    }
}

class VisitExcluded_public extends VisitExcluded
{
    public function public_isVisitorIpExcluded($ip)
    {
        return $this->isVisitorIpExcluded($ip);
    }

    public function public_isUserAgentExcluded()
    {
        return $this->isUserAgentExcluded();
    }
    public function public_isReferrerSpamExcluded()
    {
        return $this->isReferrerSpamExcluded();
    }
    public function public_isNonHumanBot()
    {
        return $this->isNonHumanBot();
    }
}
