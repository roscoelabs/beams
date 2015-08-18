<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\TestRunner\Commands;

use Piwik\Plugin;
use Piwik\Profiler;
use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Executes PHP tests.
 */
class TestsRun extends ConsoleCommand
{
    private $returnVar = 0;

    protected function configure()
    {
        $this->setName('tests:run');
        $this->setDescription('Run Piwik PHPUnit tests one testsuite after the other');
        $this->addArgument('variables', InputArgument::IS_ARRAY, 'Eg a path to a file or directory, the name of a testsuite, the name of a plugin, ... We will try to detect what you meant. You can define multiple values', array());
        $this->addOption('options', 'o', InputOption::VALUE_OPTIONAL, 'All options will be forwarded to phpunit', '');
        $this->addOption('xhprof', null, InputOption::VALUE_NONE, 'Profile using xhprof.');
        $this->addOption('group', null, InputOption::VALUE_REQUIRED, 'Run only a specific test group. Separate multiple groups by comma, for instance core,plugins', '');
        $this->addOption('file', null, InputOption::VALUE_REQUIRED, 'Execute tests within this file. Should be a path relative to the tests/PHPUnit directory.');
        $this->addOption('testsuite', null, InputOption::VALUE_REQUIRED, 'Execute tests of a specific test suite, for instance unit, integration or system.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $input->getOption('options');
        $groups  = $input->getOption('group');
        $magics  = $input->getArgument('variables');

        $groups = $this->getGroupsFromString($groups);

        $command = PIWIK_VENDOR_PATH . '/phpunit/phpunit/phpunit';

        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $command = 'php -dzend.enable_gc=0 ' . $command;
        }

        if (!$this->isCoverageEnabled($options) && $this->isXdebugLoaded()) {
            $message = 'Did you know? You can run tests faster by disabling xdebug';
            if($this->isXdebugCodeCoverageEnabled()) {
                $message .= ' (if you need xdebug, speed up tests by setting xdebug.coverage_enable=0)</comment>';
            }
            $output->writeln('<comment>' . $message .'</comment>');
        }

        // force xdebug usage for coverage options
        if ($this->isCoverageEnabled($options) && !$this->isXdebugLoaded()) {

            $output->writeln('<info>xdebug extension required for code coverage.</info>');

            $output->writeln('<info>searching for xdebug extension...</info>');

            $extensionDir = shell_exec('php-config --extension-dir');
            $xdebugFile   = trim($extensionDir) . DIRECTORY_SEPARATOR . 'xdebug.so';

            if (!file_exists($xdebugFile)) {

                $dialog = $this->getHelperSet()->get('dialog');

                $xdebugFile = $dialog->askAndValidate($output, 'xdebug not found. Please provide path to xdebug.so', function($xdebugFile) {
                    return file_exists($xdebugFile);
                });
            } else {

                $output->writeln('<info>xdebug extension found in extension path.</info>');
            }

            $output->writeln("<info>using $xdebugFile as xdebug extension.</info>");

            $phpunitPath = trim(shell_exec('which phpunit'));

            $command = sprintf('php -d zend_extension=%s %s', $xdebugFile, $phpunitPath);
        }

        if ($input->getOption('xhprof')) {
            Profiler::setupProfilerXHProf($isMainRun = true);

            putenv('PIWIK_USE_XHPROF=1');
        }

        $suite    = $this->getTestsuite($input);
        $testFile = $this->getTestFile($input);

        if (!empty($magics)) {
            foreach ($magics as $magic) {
                if (empty($suite) && (in_array($magic, $this->getTestsSuites()))) {
                    $suite = $this->buildTestSuiteName($magic);
                } elseif (empty($testFile) && 'core' === $magic) {
                    $testFile = $this->fixPathToTestFileOrDirectory('tests/PHPUnit');
                } elseif (empty($testFile) && 'plugins' === $magic) {
                    $testFile = $this->fixPathToTestFileOrDirectory('plugins');
                } elseif (empty($testFile) && file_exists($magic)) {
                    $testFile = $this->fixPathToTestFileOrDirectory($magic);
                } elseif (empty($testFile) && $this->getPluginTestFolderName($magic)) {
                    $testFile = $this->getPluginTestFolderName($magic);
                } elseif (empty($groups)) {
                    $groups = $this->getGroupsFromString($magic);
                } else {
                    $groups[] = $magic;
                }
            }
        }

        $this->executeTests($suite, $testFile, $groups, $options, $command, $output);

        return $this->returnVar;
    }

    private function getPluginTestFolderName($name)
    {
        $pluginName = $this->getPluginName($name);

        $folder = '';
        if (!empty($pluginName)) {
            $path = PIWIK_INCLUDE_PATH . '/plugins/' . $pluginName;

            if (is_dir($path . '/tests')) {
                $folder = $this->fixPathToTestFileOrDirectory($path . '/tests');
            } elseif (is_dir($path . '/Tests')) {
                $folder = $this->fixPathToTestFileOrDirectory($path . '/Tests');
            }
        }

        return $folder;
    }

    private function getPluginName($name)
    {
        $pluginNames = Plugin\Manager::getInstance()->getAllPluginsNames();

        foreach ($pluginNames as $pluginName) {
            if (strtolower($pluginName) === strtolower($name)) {
                return $pluginName;
            }
        }
    }

    private function getTestFile(InputInterface $input)
    {
        $testFile = $input->getOption('file');

        if (empty($testFile)) {
            return '';
        }

        return $this->fixPathToTestFileOrDirectory($testFile);
    }

    private function executeTests($suite, $testFile, $groups, $options, $command, OutputInterface $output)
    {
        if (empty($suite) && empty($groups) && empty($testFile)) {
            foreach ($this->getTestsSuites() as $suite) {
                $suite = $this->buildTestSuiteName($suite);
                $this->executeTests($suite, $testFile, $groups, $options, $command, $output);
            }

            return;
        }

        $params = $this->buildPhpUnitCliParams($suite, $groups, $options);

        if (!empty($testFile)) {
            $params = $params . " " . $testFile;
        }

        $this->executeTestRun($command, $params, $output);
    }

    private function executeTestRun($command, $params, OutputInterface $output)
    {
        $cmd = $this->getCommand($command, $params);
        $output->writeln('Executing command: <info>' . $cmd . '</info>');
        passthru($cmd, $returnVar);
        $output->writeln("");

        $this->returnVar += $returnVar;
    }

    private function getTestsSuites()
    {
        return array('unit', 'integration', 'system');
    }

    /**
     * @param $command
     * @param $params
     * @return string
     */
    private function getCommand($command, $params)
    {
        return sprintf('cd %s/tests/PHPUnit && %s %s', PIWIK_DOCUMENT_ROOT, $command, $params);
    }

    private function buildPhpUnitCliParams($suite, $groups, $options)
    {
        $params = $options . " ";

        if (!empty($groups)) {
            $groups  = implode(',', $groups);
            $params .= '--group ' . $groups . ' ';
        } else {
            $groups  = '';
        }

        if (!empty($suite)) {
            $params .= ' --testsuite ' . $suite;
        } else {
            $suite = '';
        }

        $params = str_replace('%suite%', $suite, $params);
        $params = str_replace('%group%', $groups, $params);

        return $params;
    }

    private function getTestsuite(InputInterface $input)
    {
        $suite = $input->getOption('testsuite');

        if (empty($suite)) {
            return;
        }

        $availableSuites = $this->getTestsSuites();

        if (!in_array($suite, $availableSuites)) {
            throw new \InvalidArgumentException('Invalid testsuite specified. Use one of: ' . implode(', ', $availableSuites));
        }

        $suite = $this->buildTestSuiteName($suite);

        return $suite;
    }

    private function buildTestSuiteName($suite)
    {
        return ucfirst($suite) . 'Tests';
    }

    private function isCoverageEnabled($options)
    {
        return false !== strpos($options, '--coverage');
    }

    private function isXdebugLoaded()
    {
        return extension_loaded('xdebug');
    }

    private function isXdebugCodeCoverageEnabled()
    {
        return (bool)ini_get('xdebug.coverage_enable');
    }

    private function fixPathToTestFileOrDirectory($testFile)
    {
        if ('/' !== substr($testFile, 0, 1)) {
            $testFile = '../../' . $testFile;
        }

        return $testFile;
    }

    private function getGroupsFromString($groups)
    {
        $groups = explode(",", $groups);
        $groups = array_filter($groups, 'strlen');

        return $groups;
    }

}
