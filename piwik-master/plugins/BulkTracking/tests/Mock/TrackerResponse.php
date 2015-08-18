<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\BulkTracking\tests\Mock;

use Piwik\Tests\Framework\Mock\Tracker\Response;

class TrackerResponse extends Response
{
    private $invalidRequests = 0;

    public function setInvalidCount($invalidRequests)
    {
        $this->invalidRequests = $invalidRequests;
    }
}