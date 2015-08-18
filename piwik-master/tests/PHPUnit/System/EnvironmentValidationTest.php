<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\System;

use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\SystemTestCase;

/**
 * @group System
 */
class EnvironmentValidationTest extends SystemTestCase
{
    public function getEntryPointsToTest()
    {
        return array(
            array('tracker'),
            array('web'),
            array('console'),
            array('archive_web')
        );
    }

    public function setUp()
    {
        parent::setUp();

        $testingEnvironment = new \Piwik\Tests\Framework\TestingEnvironmentVariables();
        $testingEnvironment->configFileGlobal = null;
        $testingEnvironment->configFileLocal = null;
        $testingEnvironment->configFileCommon = null;
        $testingEnvironment->loadRealTranslations = true;
        $testingEnvironment->save();
    }

    /**
     * @dataProvider getEntryPointsToTest
     */
    public function test_NoGlobalConfigFile_TriggersError($entryPoint)
    {
        $this->simulateAbsentConfigFile('global.ini.php');

        $output = $this->triggerPiwikFrom($entryPoint);
        $this->assertOutputContainsConfigFileMissingError('global.ini.php', $output);
    }

    public function getEntryPointsThatErrorWithNoLocal()
    {
        return array(
            array('tracker'),
            array('console')
        );
    }

    /**
     * @dataProvider getEntryPointsThatErrorWithNoLocal
     */
    public function test_NoLocalConfigFile_TriggersError($entryPoint)
    {
        $this->simulateAbsentConfigFile('config.ini.php');

        $output = $this->triggerPiwikFrom($entryPoint);
        $this->assertOutputContainsConfigFileMissingError('config.ini.php', $output);
    }

    public function test_NoLocalConfigFile_StartsInstallation_PiwikAccessedThroughWeb()
    {
        $this->simulateAbsentConfigFile('config.ini.php');

        $output = $this->triggerPiwikFrom('web');
        $this->assertInstallationProcessStarted($output);
    }

    public function getEntryPointsAndConfigFilesToTest()
    {
        return array(
            array('global.ini.php', 'tracker'),
            array('global.ini.php', 'web'),
            array('global.ini.php', 'console'),
            array('global.ini.php', 'archive_web'),

            array('config.ini.php', 'tracker'),
            array('config.ini.php', 'web'),
            array('config.ini.php', 'console'),
            array('config.ini.php', 'archive_web'),

            array('common.config.ini.php', 'tracker'),
            array('common.config.ini.php', 'web'),
            array('common.config.ini.php', 'console'),
            array('common.config.ini.php', 'archive_web'),
        );
    }

    /**
     * @dataProvider getEntryPointsAndConfigFilesToTest
     */
    public function test_BadConfigFile_TriggersError($configFile, $entryPoint)
    {
        $this->simulateBadConfigFile($configFile);

        $output = $this->triggerPiwikFrom($entryPoint);
        $this->assertOutputContainsBadConfigFileError($output);
    }

    /**
     * @dataProvider getEntryPointsToTest
     */
    public function test_BadDomainSpecificLocalConfigFile_TriggersError($entryPoint)
    {
        $this->simulateHost('piwik.kobra.org');

        $configFile = 'piwik.kobra.org.config.ini.php';
        $this->simulateBadConfigFile($configFile);

        $output = $this->triggerPiwikFrom($entryPoint);
        $this->assertOutputContainsBadConfigFileError($output);
    }

    private function assertOutputContainsConfigFileMissingError($fileName, $output)
    {
        $this->assertRegExp("/The configuration file \\{.*\\/" . preg_quote($fileName) . "\\} has not been found or could not be read\\./", $output);
    }

    private function assertOutputContainsBadConfigFileError($output)
    {
        $this->assertRegExp("/Unable to read INI file \\{.*\\/piwik.php\\}:/", $output);
        $this->assertRegExp("/Your host may have disabled parse_ini_file\\(\\)/", $output);
    }

    private function assertInstallationProcessStarted($output)
    {
        $this->assertContains('<title>Piwik &rsaquo; Installation</title>', $output);
    }

    private function simulateAbsentConfigFile($fileName)
    {
        $testingEnvironment = new \Piwik\Tests\Framework\TestingEnvironmentVariables();

        if ($fileName == 'global.ini.php') {
            $testingEnvironment->configFileGlobal = PIWIK_INCLUDE_PATH . '/tmp/nonexistant/global.ini.php';
        } else if ($fileName == 'common.config.ini.php') {
            $testingEnvironment->configFileCommon = PIWIK_INCLUDE_PATH . '/tmp/nonexistant/common.config.ini.php';
        } else {
            $testingEnvironment->configFileLocal = PIWIK_INCLUDE_PATH . '/tmp/nonexistant/' . $fileName;
        }

        $testingEnvironment->save();
    }

    private function simulateBadConfigFile($fileName)
    {
        $testingEnvironment = new \Piwik\Tests\Framework\TestingEnvironmentVariables();

        if ($fileName == 'global.ini.php') {
            $testingEnvironment->configFileGlobal = PIWIK_INCLUDE_PATH . '/piwik.php';
        } else if ($fileName == 'common.config.ini.php') {
            $testingEnvironment->configFileCommon = PIWIK_INCLUDE_PATH . '/piwik.php';
        } else {
            $testingEnvironment->configFileLocal = PIWIK_INCLUDE_PATH . '/piwik.php';
        }

        $testingEnvironment->save();
    }

    private function simulateHost($host)
    {
        $testingEnvironment = new \Piwik\Tests\Framework\TestingEnvironmentVariables();
        $testingEnvironment->hostOverride = $host;
        $testingEnvironment->save();
    }

    private function triggerPiwikFrom($entryPoint)
    {
        if ($entryPoint == 'tracker') {
            return $this->sendRequestToTracker();
        } else if ($entryPoint == 'web') {
            return $this->sendRequestToWeb();
        } else if ($entryPoint == 'console') {
            return $this->startConsoleProcess();
        } else if ($entryPoint == 'archive_web') {
            return $this->sendArchiveWebRequest();
        } else {
            throw new \Exception("Don't know how to access '$entryPoint'.");
        }
    }

    private function sendRequestToTracker()
    {
        return $this->curl(Fixture::getRootUrl() . 'tests/PHPUnit/proxy/piwik.php?idsite=1&rec=1&action_name=something');
    }

    private function sendRequestToWeb()
    {
        return $this->curl(Fixture::getRootUrl() . 'tests/PHPUnit/proxy/index.php');
    }

    private function sendArchiveWebRequest()
    {
        return $this->curl(Fixture::getRootUrl() . 'tests/PHPUnit/proxy/archive.php?token_auth=' . Fixture::getTokenAuth());
    }

    private function startConsoleProcess()
    {
        $pathToProxyConsole = PIWIK_INCLUDE_PATH . '/tests/PHPUnit/proxy/console';
        return shell_exec("php '$pathToProxyConsole' list 2>&1");
    }

    private function curl($url)
    {
        if (!function_exists('curl_init')) {
            $this->markTestSkipped('Curl is not installed');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $response = substr($response, $headerSize);

        curl_close($ch);

        return $response;
    }
}