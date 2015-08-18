<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Unit\Tracker;

use Piwik\Tracker\Request;
use Piwik\Tracker\RequestSet;

/**
 * @group RequestSetTest
 * @group Tracker
 */
class RequestSetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TestRequestSet
     */
    private $requestSet;
    private $time;

    public function setUp()
    {
        parent::setUp();

        $this->requestSet = $this->createRequestSet();
        $this->time = time();
    }

    private function createRequestSet()
    {
        return new TestRequestSet();
    }

    public function test_internalBuildRequest_ShoulBuildOneRequest()
    {
        $request = new Request(array('idsite' => '2'));
        $request->setCurrentTimestamp($this->time);

        $this->assertEquals($request, $this->buildRequest(2));
    }

    public function test_internalBuildRequests_ShoulBuildASetOfRequests()
    {
        $this->assertEquals(array(), $this->buildRequests(0));

        $this->assertEquals(array($this->buildRequest(1)), $this->buildRequests(1));

        $this->assertEquals(array(
            $this->buildRequest(1),
            $this->buildRequest(2),
            $this->buildRequest(3)
        ), $this->buildRequests(3));
    }

    public function test_getRequests_shouldReturnEmptyArray_IfThereAreNoRequestsInitializedYet()
    {
        $this->assertEquals(array(), $this->requestSet->getRequests());
    }

    public function test_setRequests_shouldNotFail_IfEmptyArrayGiven()
    {
        $this->requestSet->setRequests(array());
        $this->assertEquals(array(), $this->requestSet->getRequests());
    }

    public function test_setRequests_shouldSetAndOverwriteRequests()
    {
        $this->requestSet->setRequests($this->buildRequests(3));
        $this->assertEquals($this->buildRequests(3), $this->requestSet->getRequests());

        // overwrite
        $this->requestSet->setRequests($this->buildRequests(5));
        $this->assertEquals($this->buildRequests(5), $this->requestSet->getRequests());

        // overwrite
        $this->requestSet->setRequests($this->buildRequests(1));
        $this->assertEquals($this->buildRequests(1), $this->requestSet->getRequests());

        // clear
        $this->requestSet->setRequests(array());
        $this->assertEquals(array(), $this->requestSet->getRequests());
    }

    public function test_setRequests_shouldConvertNonRequestInstancesToARequestInstance()
    {
        $requests = array(
            $this->buildRequest(5),
            array('idsite' => 9),
            $this->buildRequest(2),
            array('idsite' => 3),
            $this->buildRequest(6)
        );

        $this->requestSet->setRequests($requests);

        $setRequests = $this->requestSet->getRequests();
        $this->assertEquals($this->buildRequest(5), $setRequests[0]);
        $this->assertEquals($this->buildRequest(2), $setRequests[2]);
        $this->assertEquals($this->buildRequest(6), $setRequests[4]);

        $this->assertTrue($setRequests[1] instanceof Request);
        $this->assertEquals(array('idsite' => 9), $setRequests[1]->getParams());

        $this->assertTrue($setRequests[3] instanceof Request);
        $this->assertEquals(array('idsite' => 3), $setRequests[3]->getParams());

        $this->assertCount(5, $setRequests);
    }

    public function test_setRequests_shouldIgnoreEmptyRequestsButNotArrays()
    {
        $requests = array(
            $this->buildRequest(5),
            null,
            $this->buildRequest(2),
            0,
            $this->buildRequest(6),
            array()
        );

        $this->requestSet->setRequests($requests);

        $expected = array($this->buildRequest(5), $this->buildRequest(2), $this->buildRequest(6), new Request(array()));
        $this->assertEquals($expected, $this->requestSet->getRequests());
    }

    public function test_getNumberOfRequests_shouldReturnZeroIfNothingSet()
    {
        $this->assertEquals(0, $this->requestSet->getNumberOfRequests());
    }

    public function test_getNumberOfRequests_shouldReturnNumberOfRequests()
    {
        $this->requestSet->setRequests($this->buildRequests(3));
        $this->assertSame(3, $this->requestSet->getNumberOfRequests());

        $this->requestSet->setRequests($this->buildRequests(5));
        $this->assertSame(5, $this->requestSet->getNumberOfRequests());

        $this->requestSet->setRequests($this->buildRequests(1));
        $this->assertSame(1, $this->requestSet->getNumberOfRequests());
    }

    public function test_hasRequests_shouldReturnFalse_IfNotInitializedYetOrNoDataSet()
    {
        $this->assertFalse($this->requestSet->hasRequests());

        $this->requestSet->setRequests(array());
        $this->assertFalse($this->requestSet->hasRequests());
    }

    public function test_hasRequests_shouldReturnTrue_IfAtLeastOneRequestIsSet()
    {
        $this->assertFalse($this->requestSet->hasRequests());

        $this->requestSet->setRequests($this->buildRequests(1));
        $this->assertTrue($this->requestSet->hasRequests());

        $this->requestSet->setRequests($this->buildRequests(5));
        $this->assertTrue($this->requestSet->hasRequests());

        $this->requestSet->setRequests(array(null, 0));
        $this->assertFalse($this->requestSet->hasRequests());
    }

    public function test_getTokenAuth_ShouldReturnFalse_IfNoTokenIsSetAndNoRequestParam()
    {
        $this->assertFalse($this->requestSet->getTokenAuth());
    }

    public function test_getTokenAuth_setTokenAuth_shouldOverwriteTheToken()
    {
        $this->requestSet->setTokenAuth('MKyKTokenTestIn');

        $this->assertEquals('MKyKTokenTestIn', $this->requestSet->getTokenAuth());
    }

    public function test_getTokenAuth_setTokenAuth_shouldBePossibleToClearASetToken()
    {
        $this->requestSet->setTokenAuth('MKyKTokenTestIn');
        $this->assertNotEmpty($this->requestSet->getTokenAuth());

        $this->requestSet->setTokenAuth(null);
        $this->assertFalse($this->requestSet->getTokenAuth()); // does now fallback to get param
    }

    public function test_getTokenAuth_ShouldFallbackToRequestParam_IfNoTokenSet()
    {
        $_GET['token_auth'] = 'MyTokenAuthTest';

        $this->assertSame('MyTokenAuthTest', $this->requestSet->getTokenAuth());

        unset($_GET['token_auth']);
    }

    public function test_getEnvironment_shouldReturnCurrentServerVar()
    {
        $this->assertEquals(array(
            'server' => $_SERVER
        ), $this->requestSet->getEnvironment());
    }

    public function test_intertnalFakeEnvironment_shouldActuallyReturnAValue()
    {
        $myEnv = $this->getFakeEnvironment();
        $this->assertInternalType('array', $myEnv);
        $this->assertNotEmpty($myEnv);
    }

    public function test_setEnvironment_shouldOverwriteAnEnvironment()
    {
        $this->requestSet->setEnvironment($this->getFakeEnvironment());

        $this->assertEquals($this->getFakeEnvironment(), $this->requestSet->getEnvironment());
    }

    public function test_restoreEnvironment_shouldRestoreAPreviouslySetEnvironment()
    {
        $serverBackup = $_SERVER;

        $this->requestSet->setEnvironment($this->getFakeEnvironment());
        $this->requestSet->restoreEnvironment();

        $this->assertEquals(array('mytest' => 'test'), $_SERVER);

        $_SERVER = $serverBackup;
    }

    public function test_rememberEnvironment_shouldSaveCurrentEnvironment()
    {
        $expected = $_SERVER;

        $this->requestSet->rememberEnvironment();

        $this->assertEquals(array('server' => $expected), $this->requestSet->getEnvironment());

        // should not change anything
        $this->requestSet->restoreEnvironment();
        $this->assertEquals($expected, $_SERVER);
    }

    public function test_getState_shouldReturnCurrentStateOfRequestSet()
    {
        $this->requestSet->setRequests($this->buildRequests(2));
        $this->requestSet->setTokenAuth('mytoken');

        $state = $this->requestSet->getState();

        $expectedKeys = array('requests', 'env', 'tokenAuth', 'time');
        $this->assertEquals($expectedKeys, array_keys($state));

        $expectedRequests = array(
            array('idsite' => 1),
            array('idsite' => 2)
        );

        $this->assertEquals($expectedRequests, $state['requests']);
        $this->assertEquals('mytoken', $state['tokenAuth']);
        $this->assertTrue(is_numeric($state['time']));
        $this->assertEquals(array('server' => $_SERVER), $state['env']);
    }

    public function test_getState_shouldRememberAnyAddedParamsFromRequestConstructor()
    {
        $_SERVER['HTTP_REFERER'] = 'test';

        $requests = $this->buildRequests(1);

        $this->requestSet->setRequests($requests);
        $this->requestSet->setTokenAuth('mytoken');

        $state = $this->requestSet->getState();

        unset($_SERVER['HTTP_REFERER']);

        $expectedRequests = array(
            array('idsite' => 1)
        );

        $this->assertEquals($expectedRequests, $state['requests']);

        // the actual params include an added urlref param which should NOT be in the state. otherwise we cannot detect empty requests etc
        $this->assertEquals(array('idsite' => 1, 'url' => 'test'), $requests[0]->getParams());
    }

    public function test_restoreState_shouldRestoreRequestSet()
    {
        $serverBackup = $_SERVER;

        $state = array(
            'requests' => array(array('idsite' => 1), array('idsite' => 2), array('idsite' => 3)),
            'time' => $this->time,
            'tokenAuth' => 'tokenAuthRestored',
            'env' => $this->getFakeEnvironment()
        );

        $this->requestSet->restoreState($state);

        $this->assertEquals($this->getFakeEnvironment(), $this->requestSet->getEnvironment());
        $this->assertEquals('tokenAuthRestored', $this->requestSet->getTokenAuth());

        $expectedRequests = array(
            new Request(array('idsite' => 1), 'tokenAuthRestored'),
            new Request(array('idsite' => 2), 'tokenAuthRestored'),
            new Request(array('idsite' => 3), 'tokenAuthRestored'),
        );
        $expectedRequests[0]->setCurrentTimestamp($this->time);
        $expectedRequests[1]->setCurrentTimestamp($this->time);
        $expectedRequests[2]->setCurrentTimestamp($this->time);

        $requests = $this->requestSet->getRequests();
        $this->assertEquals($expectedRequests, $requests);

        // verify again just to be sure (only first one)
        $this->assertEquals('tokenAuthRestored', $requests[0]->getTokenAuth());
        $this->assertEquals($this->time, $requests[0]->getCurrentTimestamp());

        // should not restoreEnvironment, only set the environment
        $this->assertSame($serverBackup, $_SERVER);
    }

    public function test_restoreState_ifRequestWasEmpty_ShouldBeStillEmptyWhenRestored()
    {
        $_SERVER['HTTP_REFERER'] = 'test';

        $this->requestSet->setRequests(array(new Request(array())));
        $state = $this->requestSet->getState();

        $requestSet = $this->createRequestSet();
        $requestSet->restoreState($state);

        unset($_SERVER['HTTP_REFERER']);

        $requests = $requestSet->getRequests();
        $this->assertTrue($requests[0]->isEmptyRequest());
    }

    public function test_restoreState_shouldResetTheStoredEnvironmentBeforeRestoringRequests()
    {
        $this->requestSet->setRequests(array(new Request(array())));
        $state = $this->requestSet->getState();
        $state['env']['server']['HTTP_REFERER'] = 'mytesturl';

        $requestSet = $this->createRequestSet();
        $requestSet->restoreState($state);

        $requests = $requestSet->getRequests();
        $this->assertTrue($requests[0]->isEmptyRequest());
        $this->assertEquals(array('url' => 'mytesturl'), $requests[0]->getParams());
        $this->assertTrue(empty($_SERVER['HTTP_REFERER']));
    }

    public function test_getRedirectUrl_ShouldReturnEmptyString_IfNoUrlSet()
    {
        $this->assertEquals('', $this->requestSet->getRedirectUrl());
    }

    public function test_getRedirectUrl_ShouldReturnTrue_IfAUrlSetIsSetViaGET()
    {
        $_GET['redirecturl'] = 'whatsoever';
        $this->assertEquals('whatsoever', $this->requestSet->getRedirectUrl());
        unset($_GET['redirecturl']);
    }

    public function test_getRedirectUrl_ShouldReturnTrue_IfAUrlSetIsSetViaPOST()
    {
        $_POST['redirecturl'] = 'whatsoeverPOST';
        $this->assertEquals('whatsoeverPOST', $this->requestSet->getRedirectUrl());
        unset($_POST['redirecturl']);
    }

    public function test_hasRedirectUrl_ShouldReturnFalse_IfNoUrlSet()
    {
        $this->assertFalse($this->requestSet->hasRedirectUrl());
    }

    public function test_hasRedirectUrl_ShouldReturnTrue_IfAUrlSetIsSetViaGET()
    {
        $_GET['redirecturl'] = 'whatsoever';
        $this->assertTrue($this->requestSet->hasRedirectUrl());
        unset($_GET['redirecturl']);
    }

    public function test_hasRedirectUrl_ShouldReturnTrue_IfAUrlSetIsSetViaPOST()
    {
        $_POST['redirecturl'] = 'whatsoever';
        $this->assertTrue($this->requestSet->hasRedirectUrl());
        unset($_POST['redirecturl']);
    }

    public function test_getAllSiteIdsWithinRequest_ShouldReturnEmptyArray_IfNoRequestsSet()
    {
        $this->assertEquals(array(), $this->requestSet->getAllSiteIdsWithinRequest());
    }

    public function test_getAllSiteIdsWithinRequest_ShouldReturnTheSiteIds_FromRequests()
    {
        $this->requestSet->setRequests($this->buildRequests(3));

        $this->assertEquals(array(1, 2, 3), $this->requestSet->getAllSiteIdsWithinRequest());
    }

    public function test_getAllSiteIdsWithinRequest_ShouldReturnUniqueSiteIds_Unordered()
    {
        $this->requestSet->setRequests(array(
            $this->buildRequest(1),
            $this->buildRequest(5),
            $this->buildRequest(1),
            $this->buildRequest(2),
            $this->buildRequest(2),
            $this->buildRequest(9),
        ));

        $this->assertEquals(array(1, 5, 2, 9), $this->requestSet->getAllSiteIdsWithinRequest());
    }

    /**
     * @param int $numRequests
     * @return Request[]
     */
    private function buildRequests($numRequests)
    {
        $requests = array();
        for ($index = 1; $index <= $numRequests; $index++) {
            $requests[] = $this->buildRequest($index);
        }
        return $requests;
    }

    private function buildRequest($idsite)
    {
        $request = new Request(array('idsite' => ('' . $idsite)));
        $request->setCurrentTimestamp($this->time);

        return $request;
    }

    private function getFakeEnvironment()
    {
        return array('server' => array('mytest' => 'test'));
    }
}

class TestRequestSet extends RequestSet
{
    public function getRedirectUrl()
    {
        return parent::getRedirectUrl();
    }

    public function hasRedirectUrl()
    {
        return parent::hasRedirectUrl();
    }

    public function getAllSiteIdsWithinRequest()
    {
        return parent::getAllSiteIdsWithinRequest();
    }

    public function getEnvironment()
    {
        return parent::getEnvironment();
    }
}
