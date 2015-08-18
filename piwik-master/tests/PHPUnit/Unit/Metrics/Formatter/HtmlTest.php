<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Tests\Unit\Metrics\Formatter;

use Piwik\Intl\Locale;
use Piwik\Metrics\Formatter\Html;
use Piwik\Translate;
use Piwik\Plugins\SitesManager\API as SitesManagerAPI;

/**
 * @group Core
 */
class HtmlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Html
     */
    private $formatter;

    private $sitesInfo;

    public function setUp()
    {
        $this->sitesInfo = array(
            1 => array(
                'idsite' => '1',
                'currency' => 'EUR'
            )
        );

        $this->formatter = new Html();

        Translate::loadAllTranslations();
        $this->setSiteManagerApiMock();
    }

    public function tearDown()
    {
        Translate::reset();
        $this->unsetSiteManagerApiMock();
    }

    public function test_getPrettyTimeFromSeconds_DefaultsToShowingSentences_AndUsesNonBreakingSpaces()
    {
        $expected = '1&nbsp;days&nbsp;10&nbsp;hours';
        $value = $this->formatter->getPrettyTimeFromSeconds(86400 + 3600 * 10);

        $this->assertEquals($expected, $value);
    }

    public function test_getPrettySizeFromBytes_UsesNonBreakingSpaces()
    {
        $expected = '1.5&nbsp;K';
        $value = $this->formatter->getPrettySizeFromBytes(1536);

        $this->assertEquals($expected, $value);
    }

    public function test_getPrettySizeFromBytes_InFixedUnitThatIsHigherThanBestUnit()
    {
        $expected = '0.001465&nbsp;M';
        $value = $this->formatter->getPrettySizeFromBytes(1536, 'M', 6);

        $this->assertEquals($expected, $value);
    }

    public function test_getPrettySizeFromBytes_InUnitThatIsLowerThanBestUnit()
    {
        $expected = '1536&nbsp;B';
        $value = $this->formatter->getPrettySizeFromBytes(1536, 'B');

        $this->assertEquals($expected, $value);
    }

    public function test_getPrettyMoney_UsesNonBreakingSpaces()
    {
        $expected = '1&nbsp;€';
        $value = $this->formatter->getPrettyMoney(1, 1);

        $this->assertEquals($expected, $value);
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