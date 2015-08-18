<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Integration;

use Piwik\Plugin\Dependency;
use Piwik\Plugin\Manager as PluginManager;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Version;

/**
 * @group Core
 */
class DependencyTest extends IntegrationTestCase
{
    /**
     * @var Dependency
     */
    private $dependency;

    public function setUp()
    {
        parent::setUp();
        $this->dependency = new Dependency();
    }

    public function test_getMissingDependencies_shouldReturnEmptyArray_IfNoInputGiven()
    {
        $this->assertMissingDependency(null, array());
        $this->assertMissingDependency(array(), array());
    }

    public function test_getMissingDependencies_EmptyVersion_ShouldBeIgnored()
    {
        $this->assertMissingDependency(array('php' => ''), array());
    }

    public function test_getMissingDependencies_multipleConditions()
    {
        $this->assertMissingDependency(array('php' => '<5.2', 'piwik' => '<2.0'), array(
            $this->missingPhp('<5.2'),
            $this->missingPiwik('<2.0')
        ));

        $this->assertMissingDependency(array('php' => '<5.2', 'piwik' => '<9.0'), array(
            $this->missingPhp('<5.2')
        ));

        $this->assertMissingDependency(array('php' => '<9.2', 'piwik' => '<2.0'), array(
            $this->missingPiwik('<2.0')
        ));

        $this->assertMissingDependency(array('php' => '<9.2', 'piwik' => '<9.0'), array());
    }

    public function test_getMissingDependencies_multipleConditions_differentConditions()
    {
        $this->assertMissingDependency(array('php' => '<5.2', 'piwik' => '>2.0'), array(
            $this->missingPhp('<5.2')
        ));

        $this->assertMissingDependency(array('php' => '>=5.3', 'piwik' => '<2.0'), array(
            $this->missingPiwik('<2.0')
        ));

        $this->assertMissingDependency(array('php' => '!=' . PHP_VERSION, 'piwik' => '<>' . Version::VERSION), array(
            $this->missingPhp('!=' . PHP_VERSION),
            $this->missingPiwik('<>' . Version::VERSION)
        ));
    }

    public function test_getMissingVersion_AND_Condition()
    {
        $this->assertMissingDependency(array('php' => '<2.0,>=9.0', 'piwik' => '<2.0'), array(
            $this->missingPhp('<2.0,>=9.0', '<2.0, >=9.0'),
            $this->missingPiwik('<2.0')
        ));
    }

    public function test_getMissingDependencies_detectsPHPVersion()
    {
        $this->assertMissingDependency(array('php' => '>=2.1'), array());
        $this->assertMissingDependency(array('php' => '>=' . PHP_VERSION), array());
        $this->assertMissingDependency(array('php' => '>' . PHP_VERSION), array(
            $this->missingPhp('>' . PHP_VERSION)
        ));
        $this->assertMissingDependency(array('php' => '>=9.2'), array(
            $this->missingPhp('>=9.2')
        ));
    }

    public function test_getMissingDependencies_detectsPiwikVersion()
    {
        $this->assertMissingDependency(array('piwik' => '>=2.1'), array());
        $this->assertMissingDependency(array('piwik' => '>=' . Version::VERSION), array());
        $this->assertMissingDependency(array('piwik' => '>' . Version::VERSION), array(
            $this->missingPiwik('>' . Version::VERSION)
        ));
        $this->assertMissingDependency(array('piwik' => '>=9.2'), array(
            $this->missingPiwik('>=9.2')
        ));
    }

    public function test_getMissingDependencies_detectUnknownDependencyName()
    {
        $this->assertMissingDependency(array('unkNowN' => '>99.99'), array(
            $this->buildMissingDependecy('unkNowN', '', '>99.99')
        ));
        $this->assertMissingDependency(array('unkNowN' => '>=0.01'), array(
            $this->buildMissingDependecy('unkNowN', '', '>=0.01')
        ));
    }

    public function test_getMissingDependencies_detectsPluginVersion()
    {
        PluginManager::getInstance()->loadAllPluginsAndGetTheirInfo();

        $this->assertMissingDependency(array('Annotations' => '>=2.1'), array());
        $this->assertMissingDependency(array('Annotations' => '>=' . Version::VERSION), array());
        $this->assertMissingDependency(array('Annotations' => '>' . Version::VERSION), array(
            $this->buildMissingDependecy('Annotations', Version::VERSION, '>' . Version::VERSION)
        ));
        $this->assertMissingDependency(array('Annotations' => '>=9.2'), array(
            $this->buildMissingDependecy('Annotations', Version::VERSION, '>=9.2')
        ));
    }

    public function test_getMissingDependencies_setPiwikVersion()
    {
        $this->assertMissingDependency(array('piwik' => '>=9.2'), array($this->missingPiwik('>=9.2')));

        $this->dependency->setPiwikVersion('9.2');

        $this->assertMissingDependency(array('piwik' => '>=9.2'), array());
    }

    public function test_getMissingVersion_EmptyCurrentAndRequiredVersion_ShouldBeIgnored()
    {
        $this->assertMissingVersion(null, null, array());
        $this->assertMissingVersion('', '', array());
    }

    public function test_getMissingVersion_EmptyCurrentVersion_ShouldBeDeclaredAsMissing()
    {
        $this->assertMissingVersion('', '5.5', array('>=5.5'));
    }

    public function test_getMissingVersion_EmptyRequiredVersion_ShouldBeIgnored()
    {
        $this->assertMissingVersion('5.5', '', array());
    }

    public function test_getMissingVersion_shouldIgnoreAnyWhitespace()
    {
        $this->assertMissingVersion('5.5 ', '5.5', array());
        $this->assertMissingVersion(' 5.5 ', '5.5', array());
        $this->assertMissingVersion('5.5', ' 5.5', array());
        $this->assertMissingVersion('5.5', ' 5.5 ', array());
    }

    public function test_getMissingVersion_NoComparisonDefined_ShouldUseGreatherThanOrEqualByDefault()
    {
        $this->assertMissingVersion('5.4', '5.2', array());
        $this->assertMissingVersion('5.4', '5.4', array());
        $this->assertMissingVersion('5.4', '9.2', array('>=9.2'));
    }

    public function test_getMissingVersion_GreatherThanOrEqual()
    {
        $this->assertMissingVersion('5.4', '>=5.2', array());
        $this->assertMissingVersion('5.4', '>=5.4', array());
        $this->assertMissingVersion('5.4', '>=9.2', array('>=9.2'));
    }

    public function test_getMissingVersion_GreatherThan()
    {
        $this->assertMissingVersion('5.4', '>5.2', array());
        $this->assertMissingVersion('5.4', '>5.4', array('>5.4'));
        $this->assertMissingVersion('5.4', '>9.2', array('>9.2'));
    }

    public function test_getMissingVersion_LowerThanOrEqual()
    {
        $this->assertMissingVersion('5.4', '<=5.2', array('<=5.2'));
        $this->assertMissingVersion('5.4', '<=5.4', array());
        $this->assertMissingVersion('5.4', '<=9.2', array());
    }

    public function test_getMissingVersion_lowerThan()
    {
        $this->assertMissingVersion('5.4', '<5.2', array('<5.2'));
        $this->assertMissingVersion('5.4', '<5.4', array('<5.4'));
        $this->assertMissingVersion('5.4', '<9.2', array());
    }

    public function test_getMissingVersion_notEqual()
    {
        $this->assertMissingVersion('5.4', '<>5.2', array());
        $this->assertMissingVersion('5.4', '<>5.4', array('<>5.4'));
        $this->assertMissingVersion('5.4', '<>9.2', array());
    }

    public function test_getMissingVersion_notEqualUsingBang()
    {
        $this->assertMissingVersion('5.4', '!=5.2', array());
        $this->assertMissingVersion('5.4', '!=5.4', array('!=5.4'));
        $this->assertMissingVersion('5.4', '!=9.2', array());
    }

    public function test_getMissingVersion_exact()
    {
        $this->assertMissingVersion('5.4', '==5.2', array('==5.2'));
        $this->assertMissingVersion('5.4', '==5.4', array());
        $this->assertMissingVersion('5.4', '==9.2', array('==9.2'));
    }

    public function test_getMissingVersion_AND_Condition_returnsOnlyNonMatchingVersions()
    {
        $this->assertMissingVersion('5.4', '<5.2,>9.0', array('<5.2', '>9.0'));
        $this->assertMissingVersion('5.4', '>5.2,<9.0', array());
        $this->assertMissingVersion('5.4', '>5.2,<9.0,<2.0', array('<2.0'));
        $this->assertMissingVersion('5.4', '>5.2,<9.0,<2.0,>=9.0', array('<2.0', '>=9.0'));
        $this->assertMissingVersion('5.4', '<2.0,>=9.0', array('<2.0', '>=9.0'));
    }

    public function test_getMissingVersion_AND_Condition_shouldIgnoreAnyWhitespace()
    {
        $this->assertMissingVersion('5.2', '5.5 , 5.4,   5.3', array('>=5.5', '>=5.4', '>=5.3'));
        $this->assertMissingVersion('5.5', '5.5 , 5.4,   5.3', array());
        $this->assertMissingVersion(' 5.2 ', '5.5 , 5.4,   5.3', array('>=5.5', '>=5.4', '>=5.3'));
        $this->assertMissingVersion(' 5.2 ', '>5.5 , <5.4,   ==5.3', array('>5.5', '==5.3'));
        $this->assertMissingVersion(' 5.2 ', '>5.5 , !=5.4,   ==5.3', array('>5.5', '==5.3'));
    }

    public function test_getMissingVersion()
    {
        $this->assertMissingVersion('5.2', '<5.2,>9.0', array('<5.2', '>9.0'));
        $this->assertMissingVersion('5.2', '<=5.2,>9.0', array('>9.0'));
        $this->assertMissingVersion('5.1', '<5.2,>9.0', array('>9.0'));
        $this->assertMissingVersion('9.1', '<5.2,>9.0', array('<5.2'));
        $this->assertMissingVersion('5.2', '>=5.2,<=9.0', array());
        $this->assertMissingVersion('9.0', '>=5.2,<=9.0', array());
        $this->assertMissingVersion('6.4', '>=5.2,<=9.0', array());
    }

    private function missingPiwik($requiredVersion, $causedBy = null)
    {
        return $this->buildMissingDependecy('piwik', Version::VERSION, $requiredVersion, $causedBy);
    }

    private function missingPhp($requiredVersion, $causedBy = null)
    {
        return $this->buildMissingDependecy('php', PHP_VERSION, $requiredVersion, $causedBy);
    }

    private function buildMissingDependecy($name, $currentVersion, $requiredVersion, $causedBy = null)
    {
        if (is_null($causedBy)) {
            $causedBy = $requiredVersion;
        }

        return array(
            'requirement'     => $name,
            'actualVersion'   => $currentVersion,
            'requiredVersion' => $requiredVersion,
            'causedBy'        => $causedBy
        );
    }

    private function assertMissingDependency($requires, $expectedMissing)
    {
        $missing = $this->dependency->getMissingDependencies($requires);

        $this->assertEquals($expectedMissing, $missing);
    }

    private function assertMissingVersion($currentVersion, $requiredVersion, $expectedMissing)
    {
        $missing = $this->dependency->getMissingVersions($currentVersion, $requiredVersion);

        $this->assertEquals($expectedMissing, $missing);
    }

}

