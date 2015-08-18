<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Unit;

use Piwik\Date;
use Piwik\Period\Day;
use Piwik\Period;
use Piwik\Period\Month;
use Piwik\Period\Week;
use Piwik\Period\Year;

class PeriodTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group Core
     */
    public function testGetId()
    {
        $period = new Day(Date::today());
        $this->assertNotEquals(0, $period->getId());
        $period = new Week(Date::today());
        $this->assertNotEquals(0, $period->getId());
        $period = new Month(Date::today());
        $this->assertNotEquals(0, $period->getId());
        $period = new Year(Date::today());
        $this->assertNotEquals(0, $period->getId());
    }

    /**
     * @group Core
     */
    public function testGetLabel()
    {
        $period = new Day(Date::today());
        $label = $period->getLabel();
        $this->assertInternalType('string', $label);
        $this->assertNotEmpty($label);
        $period = new Week(Date::today());
        $label = $period->getLabel();
        $this->assertInternalType('string', $label);
        $this->assertNotEmpty($label);
        $period = new Month(Date::today());
        $label = $period->getLabel();
        $this->assertInternalType('string', $label);
        $this->assertNotEmpty($label);
        $period = new Year(Date::today());
        $label = $period->getLabel();
        $this->assertInternalType('string', $label);
        $this->assertNotEmpty($label);
    }

    /**
     * @group Core
     */
    public function testFactoryDay()
    {
        $period = Period\Factory::build('day', Date::today());
        $this->assertInstanceOf('\Piwik\Period\Day', $period);
    }

    /**
     * @group Core
     */
    public function testFactoryMonth()
    {
        $period = Period\Factory::build('month', Date::today());
        $this->assertInstanceOf('\Piwik\Period\Month', $period);
    }

    /**
     * @group Core
     */
    public function testFactoryWeek()
    {
        $period = Period\Factory::build('week', Date::today());
        $this->assertInstanceOf('\Piwik\Period\Week', $period);
    }

    /**
     * @group Core
     */
    public function testFactoryYear()
    {
        $period = Period\Factory::build('year', Date::today());
        $this->assertInstanceOf('\Piwik\Period\Year', $period);
    }

    /**
     * @group Core
     */
    public function testFactoryInvalid()
    {
        try {
            Period\Factory::build('inValid', Date::today());
        } catch (\Exception $e) {
            return;
        }
        $this->fail('Expected Exception not raised');
    }
}