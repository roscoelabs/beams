<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Integration;

use Piwik\Cache;
use Piwik\Container\StaticContainer;
use Piwik\Piwik;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group Cache
 */
class CacheTest extends IntegrationTestCase
{
    public function test_getEagerCache_shouldPersistOnceEventWasTriggered()
    {
        $storageId = 'eagercache-test-ui';
        $cache = Cache::getEagerCache();
        $cache->save('test', 'mycontent'); // make sure something was changed, otherwise it won't save anything

        /** @var Cache\Backend $backend */
        $backend = StaticContainer::get('Piwik\Cache\Backend');
        $this->assertFalse($backend->doContains($storageId));

        Piwik::postEvent('Request.dispatch.end'); // should trigger save

        $this->assertTrue($backend->doContains($storageId));
    }
}
