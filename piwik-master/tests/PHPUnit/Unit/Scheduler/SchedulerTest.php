<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Unit\Scheduler;

use Piwik\Plugin;
use Piwik\Scheduler\Scheduler;
use Piwik\Scheduler\Task;
use Piwik\Scheduler\Timetable;
use Piwik\Tests\Framework\Mock\PiwikOption;
use Psr\Log\NullLogger;
use ReflectionProperty;

/**
 * @group Scheduler
 */
class SchedulerTest extends \PHPUnit_Framework_TestCase
{
    private static function getTestTimetable()
    {
        return array(
            'CoreAdminHome.purgeOutdatedArchives' => 1355529607,
            'PrivacyManager.deleteReportData_1'   => 1322229607,
        );
    }

    /**
     * Dataprovider for testGetScheduledTimeForMethod
     */
    public function getScheduledTimeForMethodTestCases()
    {
        $timetable = serialize(self::getTestTimetable());

        return array(
            array(1355529607, 'CoreAdminHome', 'purgeOutdatedArchives', null, $timetable),
            array(1322229607, 'PrivacyManager', 'deleteReportData', 1, $timetable),
            array(false, 'ScheduledReports', 'weeklySchedule', null, $timetable)
        );
    }

    /**
     * @dataProvider getScheduledTimeForMethodTestCases
     */
    public function testGetScheduledTimeForMethod($expectedTime, $className, $methodName, $methodParameter, $timetable)
    {
        self::stubPiwikOption($timetable);

        $taskLoader = $this->getMock('Piwik\Scheduler\TaskLoader');
        $scheduler = new Scheduler($taskLoader, new NullLogger());

        $this->assertEquals($expectedTime, $scheduler->getScheduledTimeForMethod($className, $methodName, $methodParameter));

        self::resetPiwikOption();
    }

    /**
     * Dataprovider for testRun
     */
    public function runDataProvider()
    {
        $now = time();

        $dailySchedule = $this->getMock('Piwik\Scheduler\Schedule\Daily', array('getTime'));
        $dailySchedule->expects($this->any())
            ->method('getTime')
            ->will($this->returnValue($now));

        $scheduledTaskOne = new Task($this, 'scheduledTaskOne', null, $dailySchedule);
        $scheduledTaskTwo = new Task($this, 'scheduledTaskTwo', 1, $dailySchedule);
        $scheduledTaskThree = new Task($this, 'scheduledTaskThree', null, $dailySchedule);

        $caseOneExpectedTable = array(
            __CLASS__ . '.scheduledTaskOne'   => $scheduledTaskOne->getRescheduledTime(),
            __CLASS__ . '.scheduledTaskTwo_1' => $now + 60000,
            __CLASS__ . '.scheduledTaskThree' => $scheduledTaskThree->getRescheduledTime(),
        );

        $caseTwoTimetableBeforeExecution = $caseOneExpectedTable;
        $caseTwoTimetableBeforeExecution[__CLASS__ . '.scheduledTaskThree'] = $now; // simulate elapsed time between case 1 and 2

        return array(

            // case 1) contains :
            // - scheduledTaskOne: already scheduled before, should be executed and rescheduled
            // - scheduledTaskTwo: already scheduled before, should not be executed and therefore not rescheduled
            // - scheduledTaskThree: not already scheduled before, should be scheduled but not executed
            array(
                $caseOneExpectedTable,
                // methods that should be executed
                array(
                    __CLASS__ . '.scheduledTaskOne'
                ),
                // timetable before task execution
                array(
                    __CLASS__ . '.scheduledTaskOne'   => $now,
                    __CLASS__ . '.scheduledTaskTwo_1' => $now + 60000,
                ),
                // configured tasks
                array(
                    $scheduledTaskOne,
                    $scheduledTaskTwo,
                    $scheduledTaskThree,
                )
            ),

            // case 2) follows case 1) with :
            // - scheduledTaskOne: already scheduled before, should not be executed and therefore not rescheduled
            // - scheduledTaskTwo: not configured for execution anymore, should be removed from the timetable
            // - scheduledTaskThree: already scheduled before, should be executed and rescheduled
            array(
                // expected timetable
                array(
                    __CLASS__ . '.scheduledTaskOne'   => $scheduledTaskOne->getRescheduledTime(),
                    __CLASS__ . '.scheduledTaskThree' => $scheduledTaskThree->getRescheduledTime()
                ),
                // methods that should be executed
                array(
                    __CLASS__ . '.scheduledTaskThree'
                ),
                // timetable before task execution
                $caseTwoTimetableBeforeExecution,
                // configured tasks
                array(
                    $scheduledTaskOne,
//					$scheduledTaskTwo, Not configured anymore (ie. not returned after TaskScheduler::GET_TASKS_EVENT is issued)
                    $scheduledTaskThree,
                )
            ),
        );
    }

    public function scheduledTaskOne()
    {
        // nothing to do
    }
    public function scheduledTaskTwo($param)
    {
        // nothing to do
    }
    public function scheduledTaskThree()
    {
        // nothing to do
    }

    /**
     * @dataProvider runDataProvider
     */
    public function testRun($expectedTimetable, $expectedExecutedTasks, $timetableBeforeTaskExecution, $configuredTasks)
    {
        $taskLoader = $this->getMock('Piwik\Scheduler\TaskLoader');
        $taskLoader->expects($this->atLeastOnce())
            ->method('loadTasks')
            ->willReturn($configuredTasks);

        // stub the piwik option object to control the returned option value
        self::stubPiwikOption(serialize($timetableBeforeTaskExecution));

        $scheduler = new Scheduler($taskLoader, new NullLogger());

        // execute tasks
        $executionResults = $scheduler->run();

        // assert methods are executed
        $executedTasks = array();
        foreach ($executionResults as $executionResult) {
            $executedTasks[] = $executionResult['task'];
            $this->assertNotEmpty($executionResult['output']);
        }
        $this->assertEquals($expectedExecutedTasks, $executedTasks);

        // assert the timetable is correctly updated
        $timetable = new Timetable();
        $this->assertEquals($expectedTimetable, $timetable->getTimetable());

        self::resetPiwikOption();
    }

    /**
     * @dataProvider runDataProvider
     */
    public function testRunTaskNow($expectedTimetable, $expectedExecutedTasks, $timetableBeforeTaskExecution, $configuredTasks)
    {
        $taskLoader = $this->getMock('Piwik\Scheduler\TaskLoader');
        $taskLoader->expects($this->atLeastOnce())
            ->method('loadTasks')
            ->willReturn($configuredTasks);

        // stub the piwik option object to control the returned option value
        self::stubPiwikOption(serialize($timetableBeforeTaskExecution));

        $timetable = new Timetable();
        $initialTimetable = $timetable->getTimetable();

        $scheduler = new Scheduler($taskLoader, new NullLogger());

        foreach ($configuredTasks as $task) {
            /** @var Task $task */
            $result = $scheduler->runTaskNow($task->getName());

            $this->assertNotEmpty($result);
        }

        // assert the timetable is NOT updated
        $this->assertSame($initialTimetable, $timetable->getTimetable());

        self::resetPiwikOption();
    }

    private static function stubPiwikOption($timetable)
    {
        self::getReflectedPiwikOptionInstance()->setValue(new PiwikOption($timetable));
    }

    private static function resetPiwikOption()
    {
        self::getReflectedPiwikOptionInstance()->setValue(null);
    }

    private static function getReflectedPiwikOptionInstance()
    {
        $piwikOptionInstance = new ReflectionProperty('Piwik\Option', 'instance');
        $piwikOptionInstance->setAccessible(true);
        return $piwikOptionInstance;
    }
}
