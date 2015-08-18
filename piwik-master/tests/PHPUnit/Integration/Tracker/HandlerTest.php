<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Integration\Tracker;

use Piwik\Exception\InvalidRequestParameterException;
use Piwik\Exception\UnexpectedWebsiteFoundException;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\Tracker\Response;
use Piwik\Tests\Framework\Mock\Tracker\ScheduledTasksRunner;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Tracker;
use Piwik\Tracker\Handler;
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
     * @var Response
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

        $this->response = new Response();
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

    public function test_finish_ShouldRedirectIfThereIsAValidUrl()
    {
        $_GET['redirecturl'] = 'http://localhost/test?foo=bar';

        $this->requestSet->setRequests(array(array('idsite' => '1')));

        try {
            $this->handler->finish($this->tracker, $this->requestSet);
            $this->fail('An expected exception was not thrown');
        } catch (Exception $e) {
            $this->assertContains('Piwik would redirect you to this URL: ' . $_GET['redirecturl'], $e->getMessage());
            unset($_GET['redirecturl']);
        }
    }

    public function test_finish_ShouldRedirectIfThereIsAValidBelongingToTheSite()
    {
        $_GET['redirecturl'] = 'http://piwik.net/';

        $this->requestSet->setRequests(array(array('idsite' => '1')));

        try {
            $this->handler->finish($this->tracker, $this->requestSet);
            $this->fail('An expected exception was not thrown');
        } catch (Exception $e) {
            $this->assertContains('Piwik would redirect you to this URL: http://piwik.net/', $e->getMessage());
            unset($_GET['redirecturl']);
        }
    }

    public function test_finish_ShouldNotRedirectIfThereIsAUrlThatDoesNotBelongToAnySite()
    {
        $_GET['redirecturl'] = 'http://random.piwik.org/test?foo=bar';

        $this->requestSet->setRequests(array(array('idsite' => '1')));
        $this->handler->finish($this->tracker, $this->requestSet);
        unset($_GET['redirecturl']);

        $this->assertTrue(true);
    }

    public function test_finish_ShouldNotRedirectIfUrlIsValidButNoRequests()
    {
        $_GET['redirecturl'] = 'http://localhost/test';

        $this->requestSet->setRequests(array());
        $this->handler->finish($this->tracker, $this->requestSet);
        unset($_GET['redirecturl']);

        $this->assertTrue(true);
    }

    public function test_finish_ShoulAlsoReturnAPossibleRenderedException()
    {
        $this->executeOnException($this->buildException());
        $response = $this->handler->finish($this->tracker, $this->requestSet);

        $this->assertEquals('MyMessage', $response);
    }

    public function test_onException_ShouldOutputResponse()
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

    public function test_onException_ShouldStilRedirectIfThereIsAnExceptionAndAValidRedirectUrl()
    {
        $_GET['redirecturl'] = 'http://localhost/test?foo=bar';

        $this->requestSet->setRequests(array(array('idsite' => '1')));

        try {
            $this->handler->onException($this->tracker, $this->requestSet, $this->buildException());
            $this->fail('An expected exception was not thrown');
        } catch (Exception $e) {
            $this->assertContains('Piwik would redirect you to this URL: ' . $_GET['redirecturl'], $e->getMessage());
            unset($_GET['redirecturl']);
        }
    }

    public function test_onException_ShouldNotRethrowExceptionToExitTrackerImmediately()
    {
        $exception = $this->buildException();

        $this->handler->onException($this->tracker, $this->requestSet, $exception);
        $this->assertTrue(true);
    }

    public function test_onAllRequestsTracked_ShouldTriggerScheduledTasksIfEnabled()
    {
        $runner = new ScheduledTasksRunner();
        $runner->shouldRun = true;

        $this->handler->setScheduledTasksRunner($runner);
        $this->handler->onAllRequestsTracked($this->tracker, $this->requestSet);

        $this->assertTrue($runner->ranScheduledTasks);
    }

    public function test_onAllRequestsTracked_ShouldNotTriggerScheduledTasksIfDisabled()
    {
        $runner = new ScheduledTasksRunner();
        $runner->shouldRun = false;

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
