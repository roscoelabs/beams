<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\BulkTracking\tests\Unit;

use Piwik\Plugins\BulkTracking\Tracker\Requests;
use Piwik\Tracker\Request;

/**
 * @group BulkTracking
 * @group RequestsTest
 * @group Plugins
 */
class RequestsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Requests
     */
    private $requests;

    public function setUp()
    {
        parent::setUp();
        $this->requests = new Requests();
    }

    public function test_isUsingBulkRequest_shouldReturnFalse_IfRequestIsEmpty()
    {
        $this->assertFalse($this->requests->isUsingBulkRequest(false));
        $this->assertFalse($this->requests->isUsingBulkRequest(null));
        $this->assertFalse($this->requests->isUsingBulkRequest(''));
        $this->assertFalse($this->requests->isUsingBulkRequest(0));
    }

    public function test_isUsingBulkRequest_shouldReturnFalse_IfRequestIsNotABulkRequest()
    {
        $this->assertFalse($this->requests->isUsingBulkRequest(5));
        $this->assertFalse($this->requests->isUsingBulkRequest('test'));
        $this->assertFalse($this->requests->isUsingBulkRequest('requests'));
        $this->assertFalse($this->requests->isUsingBulkRequest('{"test": "val", "request:" []}'));
        $this->assertFalse($this->requests->isUsingBulkRequest('[5, 10, "request"]'));
    }

    public function test_isUsingBulkRequest_shouldReturnTrue_IfRequestIsABulkRequest()
    {
        $request = $this->buildRequestRawData(array(array('idsite' => '1')));
        $this->assertTrue($this->requests->isUsingBulkRequest($request));

        // don't know why this one is supposed to count as bulk request!
        $this->assertTrue($this->requests->isUsingBulkRequest("{'requests'"));
    }

    public function test_getRequestsArrayFromBulkRequest_ShouldFindRequestsAndEmptyTokenAndItShouldTrimWhitespaceFromRawData()
    {
        $requests = array(array('idsite' => '1'), array('idsite' => '2'));
        $request  = $this->buildRequestRawData($requests);

        $result   = $this->requests->getRequestsArrayFromBulkRequest(' ' . $request . '     ');

        $expected = array(array(array('idsite' => '1'), array('idsite' => '2')), '');
        $this->assertEquals($expected, $result);
    }

    public function test_getRequestsArrayFromBulkRequest_shouldRecognize()
    {
        $token = md5('2');

        $request  = $this->buildRequestRawData(array(), $token);
        $result   = $this->requests->getRequestsArrayFromBulkRequest($request);

        $expected = array(array(), $token);
        $this->assertEquals($expected, $result);
    }

    public function test_initRequestsAndTokenAuth_NoRequestsSetShouldStillFindToken()
    {
        $token = md5('2');

        $request  = json_encode(array('requests' => array(), 'token_auth' => $token));
        $result   = $this->requests->initRequestsAndTokenAuth($request);

        $expected = array(array(), $token);
        $this->assertEquals($expected, $result);
    }

    public function test_initRequestsAndTokenAuth_ShouldFindRequestsAndEmptyToken()
    {
        $params  = array(array('idsite' => '1'), array('idsite' => '2'));
        $request = $this->buildRequestRawData($params);

        $result  = $this->requests->initRequestsAndTokenAuth($request);

        /** @var Request[] $requests */
        $requests  = $result[0];
        $tokenAuth = $result[1];

        $this->assertEquals('', $tokenAuth); // none was set

        $this->assertEquals(array('idsite' => '1'), $requests[0]->getParams());
        $this->assertEquals('', $requests[0]->getTokenAuth());
        $this->assertEquals(array('idsite' => '2'), $requests[1]->getParams());
        $this->assertEquals('', $requests[1]->getTokenAuth());
        $this->assertCount(2, $requests);
    }

    public function test_initRequestsAndTokenAuth_ShouldFindRequestsAndASetTokenAndPassItToRequestInstances()
    {
        $token = md5(2);
        $params  = array(array('idsite' => '1'), array('idsite' => '2'));
        $request = $this->buildRequestRawData($params, $token);

        $result  = $this->requests->initRequestsAndTokenAuth($request);

        /** @var Request[] $requests */
        $requests  = $result[0];

        $this->assertEquals($token, $result[1]);
        $this->assertEquals($token, $requests[0]->getTokenAuth());
        $this->assertEquals($token, $requests[1]->getTokenAuth());
    }

    public function test_initRequestsAndTokenAuth_ShouldIgnoreEmptyUrls()
    {
        $token = md5(2);
        $params  = array(array('idsite' => '1'), '', array('idsite' => '2'));
        $request = $this->buildRequestRawData($params, $token);

        $result  = $this->requests->initRequestsAndTokenAuth($request);

        /** @var Request[] $requests */
        $requests  = $result[0];

        $this->assertEquals(array('idsite' => '1'), $requests[0]->getParams());
        $this->assertEquals(array('idsite' => '2'), $requests[1]->getParams());
        $this->assertCount(2, $requests);
    }

    public function test_initRequestsAndTokenAuth_ShouldResolveUrls()
    {
        $token = md5(2);
        $params  = array('piwik.php?idsite=1', '', 'piwik.php?idsite=3&rec=0', array('idsite' => '2'));
        $request = $this->buildRequestRawData($params, $token);

        $result  = $this->requests->initRequestsAndTokenAuth($request);

        /** @var Request[] $requests */
        $requests  = $result[0];

        $this->assertEquals(array('idsite' => '1'), $requests[0]->getParams());
        $this->assertEquals(array('idsite' => '3', 'rec' => '0'), $requests[1]->getParams());
        $this->assertEquals(array('idsite' => '2'), $requests[2]->getParams());
        $this->assertCount(3, $requests);
    }

    private function buildRequestRawData($requests, $token = null)
    {
        $params = array('requests' => $requests);

        if (!empty($token)) {
            $params['token_auth'] = $token;
        }

        return json_encode($params);
    }

}
