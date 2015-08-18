<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Tests\Unit\Metrics;

use Piwik\Intl\Locale;
use Piwik\Metrics\Formatter;
use Piwik\Translate;
use Piwik\Plugins\SitesManager\API as SitesManagerAPI;

/**
 * @group Core
 */
class FormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Formatter
     */
    private $formatter;

    private $sitesInfo;

    public function setUp()
    {
        $this->sitesInfo = array(
            1 => array(
                'idsite' => '1',
                'currency' => 'EUR'
            ),
            2 => array(
                'idsite' => '2',
                'currency' => 'DKK'
            ),
            3 => array(
                'idsite' => '3',
                'currency' => 'PLN'
            ),
            4 => array(
                'idsite' => '4',
                'currency' => 'NZD'
            ),
            5 => array(
                'idsite' => '5',
                'currency' => 'JPY'
            )
        );

        $this->formatter = new Formatter();

        Translate::loadAllTranslations();
        $this->setSiteManagerApiMock();
    }

    public function tearDown()
    {
        Translate::reset();
        $this->unsetSiteManagerApiMock();
    }

    /**
     * @dataProvider getPrettyNumberTestData
     */
    public function test_getPrettyNumber_ReturnsCorrectResult($number, $expected)
    {
        $this->assertEquals($expected, $this->formatter->getPrettyNumber($number, 2));
    }

    /**
     * @dataProvider getPrettyNumberLocaleTestData
     */
    public function test_getPrettyNumber_ReturnsCorrectResult_WhenLocaleIsEuropean($number, $expected)
    {
        $locale = setlocale(LC_ALL, array('de', 'de_DE', 'ge', 'de_DE.utf8'));
        if (empty($locale)) {
            $this->markTestSkipped("de_DE locale is not present on this system");
        }

        $this->assertEquals($expected, $this->formatter->getPrettyNumber($number, 2));
        Locale::setDefaultLocale();
    }

    /**
     * @dataProvider getPrettySizeFromBytesTestData
     */
    public function test_getPrettySizeFromBytes_ReturnsCorrectResult($bytesSize, $unit, $expected)
    {
        $this->assertEquals($expected, $this->formatter->getPrettySizeFromBytes($bytesSize, $unit));
    }

    /**
     * @dataProvider getPrettyMoneyTestData
     */
    public function test_getPrettyMoney_ReturnsCorrectResult($value, $idSite, $expected)
    {
        $this->assertEquals($expected, $this->formatter->getPrettyMoney($value, $idSite));
    }

    /**
     * @dataProvider getPrettyPercentFromQuotientTestData
     */
    public function test_getPrettyPercentFromQuotient_ReturnsCorrectResult($value, $expected)
    {
        $this->assertEquals($expected, $this->formatter->getPrettyPercentFromQuotient($value));
    }

    /**
     * @dataProvider getPrettyTimeFromSecondsData
     */
    public function test_getPrettyTimeFromSeconds_ReturnsCorrectResult($seconds, $expected)
    {
        if (($seconds * 100) > PHP_INT_MAX) {
            $this->markTestSkipped("Will not pass on 32-bit machine.");
        }

        $sentenceExpected = $expected[0];
        $this->assertEquals($sentenceExpected, $this->formatter->getPrettyTimeFromSeconds($seconds, $sentence = true));

        $numericExpected = $expected[1];
        $this->assertEquals($numericExpected, $this->formatter->getPrettyTimeFromSeconds($seconds, $sentence = false));
    }

    public function getPrettyNumberTestData()
    {
        return array(
            array(0.14, '0.14'),
            array(0.14567, '0.15'),
            array(100.1234, '100.12'),
            array(1000.45, '1,000.45'),
            array(23456789.00, '23,456,789.00')
        );
    }

    public function getPrettyNumberLocaleTestData()
    {
        return array(
            array(0.14, '0,14'),
            array(0.14567, '0,15'),
            array(100.1234, '100,12'),
            // Those last two are commented because locales are platform dependent, on some platforms the separator is '' instead of '.'
//            array(1000.45, '1.000,45'),
//            array(23456789.00, '23.456.789,00'),
        );
    }

    public function getPrettySizeFromBytesTestData()
    {
        return array(
            array(767, null, '767 B'),
            array(1024, null, '1 K'),
            array(1536, null, '1.5 K'),
            array(1024 * 1024, null, '1 M'),
            array(1.25 * 1024 * 1024, null, '1.3 M'),
            array(1.25 * 1024 * 1024 * 1024, null, '1.3 G'),
            array(1.25 * 1024 * 1024 * 1024 * 1024, null, '1.3 T'),
            array(1.25 * 1024 * 1024 * 1024 * 1024 * 1024, null, '1280 T'),
            array(1.25 * 1024 * 1024, 'M', '1.3 M'),
            array(1.25 * 1024 * 1024 * 1024, 'M', '1280 M'),
            array(0, null, '0 M')
        );
    }

    public function getPrettyMoneyTestData()
    {
        return array(
            array(1, 1, '1 €'),
            array(1.045, 2, '1.04 kr'),
            array(1000.4445, 3, '1000.44 zł'),
            array(1234.56, 4, '$ 1234.56'),
            array(234.76, 5, '¥ 234.76')
        );
    }

    public function getPrettyPercentFromQuotientTestData()
    {
        return array(
            array(100, '10000%'),
            array(1, '100%'),
            array(.85, '85%'),
            array(.89999, '89.999%'),
            array(.0004, '0.04%')
        );
    }

    /**
     * Dataprovider for testGetPrettyTimeFromSeconds
     */
    public function getPrettyTimeFromSecondsData()
    {
        return array(
            array(30, array('30s', '00:00:30')),
            array(60, array('1 min 0s', '00:01:00')),
            array(100, array('1 min 40s', '00:01:40')),
            array(3600, array('1 hours 0 min', '01:00:00')),
            array(3700, array('1 hours 1 min', '01:01:40')),
            array(86400 + 3600 * 10, array('1 days 10 hours', '34:00:00')),
            array(86400 * 365, array('365 days 0 hours', '8760:00:00')),
            array((86400 * (365.25 + 10)), array('1 years 10 days', '9006:00:00')),
            array(1.342, array('1.34s', '00:00:01.34')),
            array(.342, array('0.34s', '00:00:00.34')),
            array(.02, array('0.02s', '00:00:00.02')),
            array(.002, array('0.002s', '00:00:00')),
            array(1.002, array('1s', '00:00:01')),
            array(1.02, array('1.02s', '00:00:01.02')),
            array(1.2, array('1.2s', '00:00:01.20')),
            array(122.1, array('2 min 2.1s', '00:02:02.10')),
            array(-122.1, array('-2 min 2.1s', '-00:02:02.10')),
            array(86400 * -365, array('-365 days 0 hours', '-8760:00:00'))
        );
    }

    private function unsetSiteManagerApiMock()
    {
        SitesManagerAPI::unsetInstance();
    }

    private function setSiteManagerApiMock()
    {
        $sitesInfo = $this->sitesInfo;

        $mock = $this->getMock('stdClass', array('getSiteFromId'));
        $mock->expects($this->any())->method('getSiteFromId')->willReturnCallback(function ($idSite) use ($sitesInfo) {
            return $sitesInfo[$idSite];
        });

        SitesManagerAPI::setSingletonInstance($mock);
    }
}