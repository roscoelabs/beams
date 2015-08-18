<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Unit\Scheduler\Schedule;

use Piwik\Scheduler\Schedule\Monthly;

/**
 * @group Scheduler
 */
class MonthlyTest extends \PHPUnit_Framework_TestCase
{
    public static $_JANUARY_01_1971_09_00_00; // initialized below class definition
    public static $_JANUARY_02_1971_09_00_00;
    public static $_JANUARY_05_1971_09_00_00;
    public static $_JANUARY_15_1971_09_00_00;
    public static $_FEBRUARY_01_1971_00_00_00;
    public static $_FEBRUARY_02_1971_00_00_00;
    public static $_FEBRUARY_03_1971_09_00_00;
    public static $_FEBRUARY_21_1971_09_00_00;
    public static $_FEBRUARY_28_1971_00_00_00;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
    }

    /**
     * Tests invalid call to setHour on Monthly
     * @expectedException \Exception
     */
    public function testSetHourScheduledTimeMonthlyNegative()
    {
        $monthlySchedule = new Monthly();
        $monthlySchedule->setHour(-1);
    }

    /**
     * Tests invalid call to setHour on Monthly
     * @expectedException \Exception
     */
    public function testSetHourScheduledTimMonthlyOver24()
    {
        $monthlySchedule = new Monthly();
        $monthlySchedule->setHour(25);
    }

    /**
     * Tests invalid call to setDay on Monthly
     * @expectedException \Exception
     */
    public function testSetDayScheduledTimeMonthlyDay0()
    {
        $monthlySchedule = new Monthly();
        $monthlySchedule->setDay(0);
    }

    /**
     * Tests invalid call to setDay on Monthly
     * @expectedException \Exception
     */
    public function testSetDayScheduledTimeMonthlyOver31()
    {
        $monthlySchedule = new Monthly();
        $monthlySchedule->setDay(32);
    }

    /**
     * Tests getRescheduledTime on Monthly with unspecified hour and unspecified day
     */
    public function testGetRescheduledTimeMonthlyUnspecifiedHourUnspecifiedDay()
    {
        /*
         * Test 1
         *
         * Context :
         *  - getRescheduledTime called Friday January 1 1971 09:00:00 UTC
         *  - setHour is not called, defaulting to midnight
         *  - setDay is not called, defaulting to first day of the month
         *
         * Expected :
         *  getRescheduledTime returns Monday February 1 1971 00:00:00 UTC
         */
        $mock = $this->getMonthlyMock(self::$_JANUARY_01_1971_09_00_00);
        $this->assertEquals(self::$_FEBRUARY_01_1971_00_00_00, $mock->getRescheduledTime());

        /*
         * Test 2
         *
         * Context :
         *  - getRescheduledTime called Tuesday January 5 1971 09:00:00 UTC
         *  - setHour is not called, defaulting to midnight
         *  - setDay is not called, defaulting to first day of the month
         *
         * Expected :
         *  getRescheduledTime returns Monday February 1 1971 00:00:00 UTC
         */
        $mock = $this->getMonthlyMock(self::$_JANUARY_05_1971_09_00_00);
        $this->assertEquals(self::$_FEBRUARY_01_1971_00_00_00, $mock->getRescheduledTime());
    }

    public function test_setTimezone_ShouldConvertRescheduledTime()
    {
        $oneHourInSeconds = 3600;

        $mock = $this->getMonthlyMock(self::$_JANUARY_05_1971_09_00_00);
        $timeUTC = $mock->getRescheduledTime();
        $this->assertEquals(self::$_FEBRUARY_01_1971_00_00_00, $timeUTC);

        $mock->setTimezone('Pacific/Auckland');
        $timeAuckland = $mock->getRescheduledTime();
        $this->assertEquals(-13 * $oneHourInSeconds, $timeAuckland - $timeUTC);

        $mock->setTimezone('America/Los_Angeles');
        $timeLosAngeles = $mock->getRescheduledTime();
        $this->assertEquals(8 * $oneHourInSeconds, $timeLosAngeles - $timeUTC);
    }

    /**
     * Tests getRescheduledTime on Monthly with unspecified hour and specified day
     *
     * _Monthly
     *
     * @dataProvider getSpecifiedDayData
     */
    public function testGetRescheduledTimeMonthlyUnspecifiedHourSpecifiedDay($currentTime, $day, $expected)
    {
        $mock = $this->getMonthlyMock(self::$$currentTime);
        $mock->setDay($day);
        $this->assertEquals(self::$$expected, $mock->getRescheduledTime());
    }

    /**
     * DataProvider for testGetRescheduledTimeMonthlyUnspecifiedHourSpecifiedDay
     * @return array
     */
    public function getSpecifiedDayData()
    {
        return array(
            /*
             * Test 1
             *
             * Context :
             *  - getRescheduledTime called Friday January 1 1971 09:00:00 UTC
             *  - setHour is not called, defaulting to midnight
             *  - setDay is set to 1
             *
             * Expected :
             *  getRescheduledTime returns Monday February 1 1971 00:00:00 UTC
             */
            array('_JANUARY_01_1971_09_00_00', 1, '_FEBRUARY_01_1971_00_00_00'),
            /*
             * Test 2
             *
             * Context :
             *  - getRescheduledTime called Saturday January 2 1971 09:00:00 UTC
             *  - setHour is not called, defaulting to midnight
             *  - setDay is set to 2
             *
             * Expected :
             *  getRescheduledTime returns Tuesday February 2 1971 00:00:00 UTC
             */
            array('_JANUARY_02_1971_09_00_00', 2, '_FEBRUARY_02_1971_00_00_00'),
            /*
             * Test 3
             *
             * Context :
             *  - getRescheduledTime called Friday January 15 1971 09:00:00 UTC
             *  - setHour is not called, defaulting to midnight
             *  - setDay is set to 2
             *
             * Expected :
             *  getRescheduledTime returns Tuesday February 1 1971 00:00:00 UTC
             */
            array('_JANUARY_15_1971_09_00_00', 2, '_FEBRUARY_02_1971_00_00_00'),
            /*
             * Test 4
             *
             * Context :
             *  - getRescheduledTime called Friday January 15 1971 09:00:00 UTC
             *  - setHour is not called, defaulting to midnight
             *  - setDay is set to 31
             *
             * Expected :
             *  getRescheduledTime returns Sunday February 28 1971 00:00:00 UTC
             */
            array('_JANUARY_15_1971_09_00_00', 31, '_FEBRUARY_28_1971_00_00_00')
        );
    }

    /**
     * Returns the data used to test the setDayOfWeek method.
     */
    public function getValuesToTestSetDayOfWeek()
    {
        return array(
            array(3, 0, self::$_FEBRUARY_03_1971_09_00_00),
            array(0, 2, self::$_FEBRUARY_21_1971_09_00_00),
        );
    }

    /**
     * Returns the data used to test the setDayOfWeekFromString method.
     */
    public function getValuesToTestSetDayOfWeekByString()
    {
        return array(
            array('first wednesday', self::$_FEBRUARY_03_1971_09_00_00),
            array('ThIrD sUnDaY', self::$_FEBRUARY_21_1971_09_00_00)
        );
    }

    /**
     * @dataProvider getValuesToTestSetDayOfWeek
     */
    public function testMonthlyDayOfWeek($day, $week, $expectedTime)
    {
        $mock = $this->getMonthlyMock(self::$_JANUARY_15_1971_09_00_00);
        $mock->setDayOfWeek($day, $week);
        $this->assertEquals($expectedTime, $mock->getRescheduledTime());
    }

    /**
     * @dataProvider getValuesToTestSetDayOfWeekByString
     */
    public function testMonthlyDayOfWeekByString($dayOfWeekStr, $expectedTime)
    {
        $mock = $this->getMonthlyMock(self::$_JANUARY_15_1971_09_00_00);
        $mock->setDayOfWeekFromString($dayOfWeekStr);
        $this->assertEquals($expectedTime, $mock->getRescheduledTime());
    }

    /**
     * _Monthly
     *
     * @dataProvider getInvalidDayOfWeekData
     * @expectedException \Exception
     */
    public function testMonthlyDayOfWeekInvalid($day, $week)
    {
        $mock = $this->getMonthlyMock(self::$_JANUARY_15_1971_09_00_00);
        $mock->setDayOfWeek($day, $week);
    }

    /**
     * DataProvider for testMonthlyDayOfWeekInvalid
     * @return array
     */
    public function getInvalidDayOfWeekData()
    {
        return array(
            array(-4, 0),
            array(8, 0),
            array(0x8, 0),
            array('9dd', 0),
            array(1, -5),
            array(1, 5),
            array(1, 0x8),
            array(1, '9ff'),
        );
    }

    /**
     * @param $currentTime
     * @return Monthly
     */
    private function getMonthlyMock($currentTime)
    {
        $mock = $this->getMock('Piwik\Scheduler\Schedule\Monthly', array('getTime'));
        $mock->expects($this->any())
             ->method('getTime')
             ->will($this->returnValue($currentTime));

        return $mock;
    }
}

MonthlyTest::$_JANUARY_01_1971_09_00_00 = mktime(9, 00, 00, 1, 1, 1971);
MonthlyTest::$_JANUARY_02_1971_09_00_00 = mktime(9, 00, 00, 1, 2, 1971);
MonthlyTest::$_JANUARY_05_1971_09_00_00 = mktime(9, 00, 00, 1, 5, 1971);
MonthlyTest::$_JANUARY_15_1971_09_00_00 = mktime(9, 00, 00, 1, 15, 1971);
MonthlyTest::$_FEBRUARY_01_1971_00_00_00 = mktime(0, 00, 00, 2, 1, 1971);
MonthlyTest::$_FEBRUARY_02_1971_00_00_00 = mktime(0, 00, 00, 2, 2, 1971);
MonthlyTest::$_FEBRUARY_03_1971_09_00_00 = mktime(0, 00, 00, 2, 3, 1971);
MonthlyTest::$_FEBRUARY_21_1971_09_00_00 = mktime(0, 00, 00, 2, 21, 1971);
MonthlyTest::$_FEBRUARY_28_1971_00_00_00 = mktime(0, 00, 00, 2, 28, 1971);
