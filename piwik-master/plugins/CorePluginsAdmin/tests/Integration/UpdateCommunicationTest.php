<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CorePluginsAdmin\tests\Integration;

use Piwik\Config;
use Piwik\Option;
use Piwik\Plugins\CorePluginsAdmin\UpdateCommunication;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * Class Plugins_CorePluginsAdmin_UpdateCommunicationTest
 *
 * @group Plugins
 */
class UpdateCommunicationTest extends IntegrationTestCase
{
    /**
     * @var UpdateCommunication
     */
    private $updateCommunication;

    public function setUp()
    {
        parent::setUp();

        $this->updateCommunication = new UpdateCommunication();
        $this->updateCommunication->enable();
    }

    public function test_canBeEnabled()
    {
        $this->assertTrue($this->updateCommunication->canBeEnabled());

        Config::getInstance()->General['enable_update_communication'] = 0;
        $this->assertFalse($this->updateCommunication->canBeEnabled());

        Config::getInstance()->General['enable_update_communication'] = 1;
        $this->assertTrue($this->updateCommunication->canBeEnabled());
    }

    public function test_enable()
    {
        $this->updateCommunication->enable();
        $this->assertTrue($this->updateCommunication->isEnabled());
    }

    public function test_disable()
    {
        $this->assertTrue($this->updateCommunication->isEnabled());

        $this->updateCommunication->disable();
        $this->assertFalse($this->updateCommunication->isEnabled());
    }

    public function test_isEnabled_shouldReturnFalse_IfCannotBeEnabled()
    {
        $this->assertTrue($this->updateCommunication->isEnabled());

        Config::getInstance()->General['enable_update_communication'] = 0;
        $this->assertFalse($this->updateCommunication->isEnabled());
    }

    public function test_sendNotificationIfUpdatesAvailable_shouldNotSendNotification_IfNoUpdateAvailable()
    {
        $mock = $this->getCommunicationMock(array());
        $mock->expects($this->never())->method('sendEmailNotification');
        $mock->sendNotificationIfUpdatesAvailable();
    }

    /**
     * @dataProvider provideSendNotificationData
     */
    public function test_sendNotificationIfUpdatesAvailable($latestVersion, $lastSentVersion, $expects, $expectedLastSentVersion)
    {
        $pluginsHavingUpdate = array(
            array('name' => 'MyTest', 'latestVersion' => $latestVersion, 'isTheme' => false)
        );
        $this->setLastSentVersion('MyTest', $lastSentVersion);

        $mock = $this->getCommunicationMock($pluginsHavingUpdate);
        $mock->expects($expects)->method('sendEmailNotification');
        $mock->sendNotificationIfUpdatesAvailable();

        $this->assertEquals($expectedLastSentVersion, $this->getLastSentVersion('MyTest'));
    }

    public function provideSendNotificationData()
    {
        return array(
            array('33.0.0', '33.0.0', $this->never(), '33.0.0'), // shouldNotSend_IfAlreadyNotified
            array('31.0.0', '33.0.0', $this->never(), '33.0.0'), // shouldNotSend_IfAlreadyNotifiedAboutLaterRelease
            array('33.0.0', false,    $this->once(), '33.0.0'),  // shouldSend_IfUpdateAvailableAndNeverSentAnyBefore
            array('33.0.0', '31.0.0', $this->once(), '33.0.0'),  // shouldSend_IfUpdateAvailable,
        );
    }

    public function test_sendNotificationIfUpdatesAvailable_ShouldSendOnlyOneEmail_IfMultipleUpdatesAreAvailable()
    {
        $mock = $this->getCommunicationMockHavingManyUpdates();
        $mock->expects($this->once())->method('sendEmailNotification');
        $mock->sendNotificationIfUpdatesAvailable();
    }

    public function test_sendNotificationIfUpdatesAvailable_ShouldUpdateAllSentVersions_IfMultipleUpdatesAreAvailable()
    {
        $mock = $this->getCommunicationMockHavingManyUpdates();
        $mock->expects($this->once())->method('sendEmailNotification');
        $mock->sendNotificationIfUpdatesAvailable();

        $this->assertEquals('33.0.0', $this->getLastSentVersion('MyTest1'));
        $this->assertEquals('32.0.0', $this->getLastSentVersion('MyTest2'));
        $this->assertEquals('31.0.0', $this->getLastSentVersion('MyTest3'));
    }

    public function test_sendNotificationIfUpdatesAvailable_ShouldSendCorrectText()
    {
        $subject = 'CoreUpdater_NotificationSubjectAvailablePluginUpdate';
        $message = 'ScheduledReports_EmailHello

CoreUpdater_ThereIsNewPluginVersionAvailableForUpdate

 * MyTest1 33.0.0
 * MyTest2 32.0.0
 * MyTest3 31.0.0

CoreUpdater_NotificationClickToUpdatePlugins
http://localhost/tests/PHPUnit/proxy/index.php?module=CorePluginsAdmin&action=plugins

Installation_HappyAnalysing';

        $mock = $this->getCommunicationMockHavingManyUpdates();

        $mock->expects($this->once())->method('sendEmailNotification')
             ->with($this->equalTo($subject), $this->equalTo($message));

        $mock->sendNotificationIfUpdatesAvailable();
    }

    private function setLastSentVersion($pluginName, $version)
    {
        Option::set('last_update_communication_sent_plugin_' . $pluginName, $version);
    }

    private function getLastSentVersion($pluginName)
    {
        return Option::get('last_update_communication_sent_plugin_' . $pluginName);
    }

    /**
     * @param array $pluginsHavingUpdate
     * @return UpdateCommunication
     */
    private function getCommunicationMock($pluginsHavingUpdate)
    {
        $mock = $this->getMock('\Piwik\Plugins\CorePluginsAdmin\UpdateCommunication', array('getPluginsHavingUpdate', 'sendEmailNotification'));

        $mock->expects($this->any())
             ->method('getPluginsHavingUpdate')
             ->will($this->returnValue($pluginsHavingUpdate));

        return $mock;
    }

    private function getCommunicationMockHavingManyUpdates()
    {
        $pluginsHavingUpdate = array(
            array('name' => 'MyTest1', 'latestVersion' => '33.0.0', 'isTheme' => false),
            array('name' => 'MyTest2', 'latestVersion' => '32.0.0', 'isTheme' => false),
            array('name' => 'MyTest3', 'latestVersion' => '31.0.0', 'isTheme' => false),
        );

        $this->setLastSentVersion('MyTest1', false);
        $this->setLastSentVersion('MyTest2', false);
        $this->setLastSentVersion('MyTest3', false);

        $mock = $this->getCommunicationMock($pluginsHavingUpdate);

        return $mock;
    }
}
