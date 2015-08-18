<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Integration;

use Piwik\Option;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Updater;
use Piwik\Tests\Framework\Fixture;

/**
 * @group Core
 */
class UpdaterTest extends IntegrationTestCase
{
    public function testUpdaterChecksCoreVersionAndDetectsUpdateFile()
    {
        $updater = new Updater(PIWIK_INCLUDE_PATH . '/tests/resources/Updater/core/');
        $updater->markComponentSuccessfullyUpdated('core', '0.1');
        $componentsWithUpdateFile = $updater->getComponentsWithUpdateFile(array('core' => '0.3'));
        $this->assertEquals(1, count($componentsWithUpdateFile));
    }

    public function testUpdaterChecksGivenPluginVersionAndDetectsMultipleUpdateFileInOrder()
    {
        $updater = new Updater($pathToCoreUpdates = null, PIWIK_INCLUDE_PATH . '/tests/resources/Updater/%s/');
        $updater->markComponentSuccessfullyUpdated('testpluginUpdates', '0.1beta');
        $componentsWithUpdateFile = $updater->getComponentsWithUpdateFile(array('testpluginUpdates' => '0.1'));

        $this->assertEquals(1, count($componentsWithUpdateFile));
        $updateFiles = $componentsWithUpdateFile['testpluginUpdates'];
        $this->assertEquals(2, count($updateFiles));

        $path = PIWIK_INCLUDE_PATH . '/tests/resources/Updater/testpluginUpdates/';
        $expectedInOrder = array(
            $path . '0.1beta2.php' => '0.1beta2',
            $path . '0.1.php'      => '0.1'
        );
        $this->assertEquals($expectedInOrder, array_map("basename", $updateFiles));
    }

    public function testUpdaterChecksCoreAndPluginCheckThatCoreIsRanFirst()
    {
        $updater = new Updater(
            PIWIK_INCLUDE_PATH . '/tests/resources/Updater/core/',
            PIWIK_INCLUDE_PATH . '/tests/resources/Updater/%s/'
        );

        $updater->markComponentSuccessfullyUpdated('testpluginUpdates', '0.1beta');
        $updater->markComponentSuccessfullyUpdated('core', '0.1');

        $componentsWithUpdateFile = $updater->getComponentsWithUpdateFile(array(
            'testpluginUpdates' => '0.1',
            'core' => '0.3'
        ));
        $this->assertEquals(2, count($componentsWithUpdateFile));
        reset($componentsWithUpdateFile);
        $this->assertEquals('core', key($componentsWithUpdateFile));
    }

    public function testUpdateWorksAfterPiwikIsAlreadyUpToDate()
    {
        $result = Fixture::updateDatabase($force = true);
        if ($result === false) {
            throw new \Exception("Failed to force update (nothing to update).");
        }
    }

    public function testMarkComponentSuccessfullyUpdated_ShouldCreateAnOptionEntry()
    {
        $updater = $this->createUpdater();
        $updater->markComponentSuccessfullyUpdated('test_entry', '0.5');

        $value = Option::get('version_test_entry');
        $this->assertEquals('0.5', $value);
    }

    /**
     * @depends testMarkComponentSuccessfullyUpdated_ShouldCreateAnOptionEntry
     */
    public function testMarkComponentSuccessfullyUninstalled_ShouldCreateAnOptionEntry()
    {
        $updater = $this->createUpdater();
        $updater->markComponentSuccessfullyUninstalled('test_entry');

        $value = Option::get('version_test_entry');
        $this->assertFalse($value);
    }

    private function createUpdater()
    {
        return new Updater();
    }

}
