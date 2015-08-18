<?php

use Piwik\Application\Environment;
use Piwik\Container\StaticContainer;
use Piwik\Http;
use Piwik\Intl\Locale;
use Piwik\Config;
use Piwik\SettingsPiwik;
use Piwik\Tests\Framework\TestingEnvironmentManipulator;
use Piwik\Tests\Framework\TestingEnvironmentVariables;

define('PIWIK_TEST_MODE', true);
define('PIWIK_PRINT_ERROR_BACKTRACE', false);

if (!defined("PIWIK_PATH_TEST_TO_ROOT")) {
    define('PIWIK_PATH_TEST_TO_ROOT', realpath(dirname(__FILE__) . '/../..'));
}
if (!defined('PIWIK_DOCUMENT_ROOT')) {
    define('PIWIK_DOCUMENT_ROOT', PIWIK_PATH_TEST_TO_ROOT);
}
if (!defined('PIWIK_USER_PATH')) {
    define('PIWIK_USER_PATH', PIWIK_PATH_TEST_TO_ROOT);
}
if (!defined('PIWIK_INCLUDE_PATH')) {
    define('PIWIK_INCLUDE_PATH', PIWIK_PATH_TEST_TO_ROOT);
}

if (!defined('PIWIK_INCLUDE_SEARCH_PATH')) {
    define('PIWIK_INCLUDE_SEARCH_PATH', get_include_path()
        . PATH_SEPARATOR . PIWIK_INCLUDE_PATH . '/vendor/bin'
        . PATH_SEPARATOR . PIWIK_INCLUDE_PATH . '/core'
        . PATH_SEPARATOR . PIWIK_INCLUDE_PATH . '/libs'
        . PATH_SEPARATOR . PIWIK_INCLUDE_PATH . '/plugins');
}
@ini_set('include_path', PIWIK_INCLUDE_SEARCH_PATH);
@set_include_path(PIWIK_INCLUDE_SEARCH_PATH);
@ini_set('memory_limit', -1);

require_once PIWIK_INCLUDE_PATH . '/core/bootstrap.php';

require_once PIWIK_INCLUDE_PATH . '/libs/PiwikTracker/PiwikTracker.php';

if (getenv('PIWIK_USE_XHPROF') == 1) {
    \Piwik\Profiler::setupProfilerXHProf();
}

// setup container for tests
function setupRootContainer() {
    // before running tests, delete the TestingEnvironmentVariables file, since it can indirectly mess w/
    // phpunit's class loading (if a test class is loaded in bootstrap.php, phpunit can't load it from a file,
    // so executing the tests in a file will fail)
    $vars = new TestingEnvironmentVariables();
    $vars->delete();

    Environment::setGlobalEnvironmentManipulator(new TestingEnvironmentManipulator($vars));

    $rootTestEnvironment = new \Piwik\Application\Environment(null);
    $rootTestEnvironment->init();
}

setupRootContainer(); // do it in a function so it doesn't appear in $_GLOBALS and so PHPUnit won't try to serialize it.

// require test fixtures
$fixturesToLoad = array(
    '/tests/UI/Fixtures/*.php',
    '/plugins/*/tests/Fixtures/*.php',
    '/plugins/*/Test/Fixtures/*.php',
);
foreach($fixturesToLoad as $fixturePath) {
    foreach (glob(PIWIK_INCLUDE_PATH . $fixturePath) as $file) {
        require_once $file;
    }
}

Locale::setDefaultLocale();

function prepareServerVariables(Config $config)
{
    $testConfig = $config->tests;

    if ('@REQUEST_URI@' === $testConfig['request_uri']) {
        // config not done yet, if Piwik is installed we can automatically configure request_uri and http_host
        $url = \Piwik\SettingsPiwik::getPiwikUrl();

        if (!empty($url)) {
            $parsedUrl = parse_url($url);
            $testConfig['request_uri'] = $parsedUrl['path'];
            $testConfig['http_host']   = $parsedUrl['host'];
            $config->tests = $testConfig;
            $config->forceSave();
        }
    }

    $_SERVER['HTTP_HOST']   = $testConfig['http_host'];
    $_SERVER['REQUEST_URI'] = $testConfig['request_uri'];
    $_SERVER['REMOTE_ADDR'] = $testConfig['remote_addr'];
}

function prepareTestDatabaseConfig(Config $config)
{
    $testDb = $config->database_tests;

    if ('@USERNAME@' !== $testDb['username']) {
        return; // testDb is already configured, we do not want to overwrite any existing settings.
    }

    $db = $config->database;
    $testDb['username'] = $db['username'];

    if (empty($testDb['password'])) {
        $testDb['password'] = $db['password'];
    }

    if (empty($testDb['host'])) {
        $testDb['host'] = $db['host'];
    }

    $testDb['tables_prefix'] = ''; // tables_prefix has to be empty for UI tests

    $config->database_tests = $testDb;
    $config->forceSave();
}

if (!SettingsPiwik::isPiwikInstalled()) {
    throw new Exception('Piwik needs to be installed in order to run the tests');
}

$config = Config::getInstance();

prepareServerVariables($config);
prepareTestDatabaseConfig($config);
checkPiwikSetupForTests();

function checkPiwikSetupForTests()
{
    if (empty($_SERVER['REQUEST_URI'])
        || $_SERVER['REQUEST_URI'] == '@REQUEST_URI@'
    ) {
        echo "WARNING: for tests to pass, you must first:
1) Install webserver on localhost, eg. apache
2) Make these Piwik files available on the webserver, at eg. http://localhost/dev/piwik/
3) Install Piwik by going through the installation process
4) Configure tests section if needed in config/config.ini.php:
[tests]
http_host   = \"localhost\"
request_uri = \"@REQUEST_URI@\"
remote_addr = \"127.0.0.1\"

Try again.";
        exit(1);
    }

}
