<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\BulkTracking\tests\Integration;

use Piwik\Exception\InvalidRequestParameterException;
use Piwik\Exception\UnexpectedWebsiteFoundException;
use Piwik\Plugins\BulkTracking\tests\Mock\TrackerResponse;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\Tracker\ScheduledTasksRunner;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Tracker;
use Piwik\Plugins\BulkTracking\Tracker\Handler;
use Piwik\Tests\Framework\Mock\Tracker\RequestSet;
use Exception;

/**
 * @group HandlerTest
 * @group Handler
 * @group Tracker
 */
class HandlerTest extends IntegrationTestCase
{
    /**
     * @var Handler
     */
    private $handler;

    /**
     * @var TrackerResponse
     */
    private $response;

    /**
     * @var Tracker
     */
    private $tracker;

    /**
     * @var RequestSet
     */
    private $requestSet;

    public function setUp()
    {
        parent::setUp();

        Fixture::createWebsite('2014-01-01 00:00:00');
        Tracker\Cache::deleteTrackerCache();

        $this->response = new TrackerResponse();
        $this->handler  = new Handler();
        $this->handler->setResponse($this->response);
        $this->tracker  = new Tracker();
        $this->requestSet = new RequestSet();
    }

    public function test_init_ShouldInitiateResponseInstance()
    {
        $this->handler->init($this->tracker, $this->requestSet);

        $this->assertTrue($this->response->isInit);
        $this->assertFalse($this->response->isResponseOutput);
        $this->assertFalse($this->response->isSend);
    }

    public function test_finish_ShouldOutputAndSendResponse()
    {
        $response = $this->handler->finish($this->tracker, $this->requestSet);

        $this->assertEquals('My Dummy Content', $response);

        $this->assertFalse($this->response->isInit);
        $this->assertFalse($this->response->isExceptionOutput);
        $this->assertTrue($this->response->isResponseOutput);
        $this->assertTrue($this->response->isSend);
    }

    public function test_onException_ShouldOutputAndSendResponse()
    {
        $this->executeOnException($this->buildException());

        $this->assertFalse($this->response->isInit);
        $this->assertFalse($this->response->isResponseOutput);
        $this->assertTrue($this->response->isExceptionOutput);
        $this->assertFalse($this->response->isSend);
    }

    public function test_onException_ShouldPassExceptionToResponse()
    {
        $exception = $this->buildException();

        $this->executeOnException($exception);

        $this->assertSame($exception, $this->response->exception);
        $this->assertSame(500, $this->response->statusCode);
    }

    public function test_onException_ShouldSendStatusCode400IfUnexpectedWebsite()
    {
        $this->executeOnException(new UnexpectedWebsiteFoundException('test'));
        $this->assertSame(400, $this->response->statusCode);
    }

    public function test_onException_ShouldSendStatusCode400IfInvalidRequestParameterException()
    {
        $this->executeOnException(new InvalidRequestParameterException('test'));
        $this->assertSame(400, $this->response->statusCode);
    }

    public function test_onException_ShouldNotRethrowAnException()
    {
        $exception = $this->buildException();

        $this->handler->onException($this->tracker, $this->requestSet, $exception);
        $this->assertTrue(true);
    }

    public function test_onAllRequestsTracked_ShouldNeverTriggerScheduledTasksEvenIfEnabled()
    {
        $runner = new ScheduledTasksRunner();
        $runner->shouldRun = true;

        $this->handler->setScheduledTasksRunner($runner);
        $this->handler->onAllRequestsTracked($this->tracker, $this->requestSet);

        $this->assertFalse($runner->ranScheduledTasks);
    }

    public function test_process_ShouldTrackAllSetRequests()
    {
        $this->assertSame(0, $this->tracker->getCountOfLoggedRequests());

        $this->requestSet->setRequests(array(
            array('idsite' => 1, 'url' => 'http://localhost/foo?bar'),
            array('idsite' => 1, 'url' => 'http://localhost'),
        ));

        $this->handler->process($this->tracker, $this->requestSet);

        $this->assertSame(2, $this->tracker->getCountOfLoggedRequests());
    }

    private function buildException()
    {
        return new \Exception('MyMessage', 292);
    }

    private function executeOnException(Exception $exception)
    {
        try {
            $this->handler->onException($this->tracker, $this->requestSet, $exception);
        } catch (Exception $e) {
        }
    }
}
