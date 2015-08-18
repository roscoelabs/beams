<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Unit;

use Piwik\Filesystem;
use Piwik\Tests\Framework\Mock\File;

/**
 * @group Core
 */
class FilesystemTest extends \PHPUnit_Framework_TestCase
{
    private $testPath;

    public function setUp()
    {
        parent::setUp();
        $this->testPath = PIWIK_INCLUDE_PATH . '/tmp/filesystemtest';
        Filesystem::mkdir($this->testPath);
    }

    public function tearDown()
    {
        Filesystem::unlinkRecursive($this->testPath, true);

        parent::tearDown();
    }

    public function test_sortFilesDescByPathLength_shouldNotFail_IfEmptyArrayGiven()
    {
        $result = Filesystem::sortFilesDescByPathLength(array());
        $this->assertEquals(array(), $result);
    }

    public function test_sortFilesDescByPathLength_shouldNotChangeOrder_IfAllHaveSameLength()
    {
        $input  = array('xyz/1.gif', 'x/xyz.gif', 'xxyyzzgg');
        $result = Filesystem::sortFilesDescByPathLength($input);
        $this->assertEquals($input, $result);
    }

    public function test_sortFilesDescByPathLength_shouldOrderDesc_IfDifferentLengthsGiven()
    {
        $input  = array('xyz/1.gif', '1.gif', 'x', 'x/xyz.gif', 'xyz', 'xxyyzzgg', 'xyz/long.gif');
        $result = Filesystem::sortFilesDescByPathLength($input);

        $expected = array(
            'xyz/long.gif',
            'x/xyz.gif',
            'xyz/1.gif',
            'xxyyzzgg',
            '1.gif',
            'xyz',
            'x',
        );
        $this->assertEquals($expected, $result);
    }

    public function test_directoryDiff_shouldNotReturnDifference_IfBothDirectoriesAreSame()
    {
        $dir    = PIWIK_INCLUDE_PATH . '/core';
        $result = Filesystem::directoryDiff($dir, $dir);

        $this->assertEquals(array(), $result);
    }

    public function test_directoryDiff_shouldNotReturnAnything_IfTargetEmpty()
    {
        $result = Filesystem::directoryDiff($this->createSourceFiles(), $this->createEmptyTarget());

        $this->assertEquals(array(), $result);
    }

    public function test_directoryDiff_shouldReturnAllTargetFiles_IfSourceIsEmpty()
    {
        $result = Filesystem::directoryDiff($this->createEmptySource(), $this->createTargetFiles());

        $this->assertEquals(array(
            '/DataTable',
            '/DataTable/BaseFilter.php',
            '/DataTable/Bridges.php',
            '/DataTable/DataTableInterface.php',
            '/DataTable/Filter',
            '/DataTable/Manager.php',
            '/DataTable/Map.php',
            '/DataTable/Renderer',
            '/DataTable/Renderer.php',
            '/DataTable/Row',
            '/DataTable/Row.php',
            '/DataTable/Simple.php',
            '/DataTable/Renderer/Console.php',
            '/DataTable/Renderer/Csv.php',
            '/DataTable/Renderer/Html.php',
            '/DataTable/Renderer/Json.php',
            '/DataTable/Renderer/Php.php',
            '/DataTable/Renderer/Rss.php',
            '/DataTable/Renderer/Tsv.php',
            '/DataTable/Renderer/Xml',
            '/DataTable/Renderer/Xml.php',
            '/DataTable/Renderer/Xml/Other.php',
            '/DataTable/Row/DataTableSummaryRow.php'
        ), $result);
    }

    public function test_directoryDiff_shouldReturnFilesPresentInTargetButNotSource_IfSourceAndTargetGiven()
    {
        $result = Filesystem::directoryDiff($this->createSourceFiles(), $this->createTargetFiles());

        $this->assertEquals(array(
            '/DataTable/Filter',
            '/DataTable/Row',
            '/DataTable/Renderer/Json.php',
            '/DataTable/Renderer/Php.php',
            '/DataTable/Renderer/Rss.php',
            '/DataTable/Renderer/Xml',
            '/DataTable/Renderer/Xml/Other.php',
            '/DataTable/Row/DataTableSummaryRow.php',
        ), $result);
    }

    public function test_unlinkTargetFilesNotPresentInSource_shouldUnlinkFilesPresentInTargetButNotSource_IfSourceAndTargetGiven()
    {
        $source = $this->createSourceFiles();
        $target = $this->createTargetFiles();

        // make sure there is a difference between those folders
        $result = Filesystem::directoryDiff($source, $target);
        $this->assertCount(8, $result);

        Filesystem::unlinkTargetFilesNotPresentInSource($source, $target);

        // make sure there is no longer a difference
        $result = Filesystem::directoryDiff($source, $target);
        $this->assertEquals(array(), $result);

        $result = Filesystem::directoryDiff($target, $source);
        $this->assertEquals(array(
             '/DataTable/NotInTarget.php',
             '/DataTable/Renderer/NotInTarget.php'
        ), $result);
    }

    public function test_unlinkTargetFilesNotPresentInSource_shouldNotFail_IfBothEmpty()
    {
        $source = $this->createEmptySource();
        $target = $this->createEmptyTarget();

        Filesystem::unlinkTargetFilesNotPresentInSource($source, $target);
    }

    public function test_unlinkTargetFilesNotPresentInSource_shouldUnlinkAllTargetFiles_IfSourceIsEmpty()
    {
        $source = $this->createEmptySource();
        $target = $this->createTargetFiles();

        // make sure there is a difference between those folders
        $result = Filesystem::directoryDiff($source, $target);
        $this->assertNotEmpty($result);

        Filesystem::unlinkTargetFilesNotPresentInSource($source, $target);

        // make sure there is no longer a difference
        $result = Filesystem::directoryDiff($source, $target);
        $this->assertEquals(array(), $result);

        $result = Filesystem::directoryDiff($target, $source);
        $this->assertEquals(array(), $result);
    }

    private function createSourceFiles()
    {
        $source = $this->createEmptySource();
        Filesystem::mkdir($source . '/DataTable');
        Filesystem::mkdir($source . '/DataTable/Renderer');

        file_put_contents($source . '/DataTable/Renderer/Console.php', '');
        file_put_contents($source . '/DataTable/Renderer/Csv.php', '');
        file_put_contents($source . '/DataTable/Renderer/Html.php', '');
        file_put_contents($source . '/DataTable/Renderer/Tsv.php', '');
        file_put_contents($source . '/DataTable/Renderer/Xml.php', '');
        file_put_contents($source . '/DataTable/Renderer/NotInTarget.php', '');

        file_put_contents($source . '/DataTable/BaseFilter.php', '');
        file_put_contents($source . '/DataTable/Bridges.php', '');
        file_put_contents($source . '/DataTable/DataTableInterface.php', '');
        file_put_contents($source . '/DataTable/NotInTarget.php', '');
        file_put_contents($source . '/DataTable/Manager.php', '');
        file_put_contents($source . '/DataTable/Map.php', '');
        file_put_contents($source . '/DataTable/Renderer.php', '');
        file_put_contents($source . '/DataTable/Row.php', '');
        file_put_contents($source . '/DataTable/Simple.php', '');

        return $source;
    }

    private function createTargetFiles()
    {
        $target = $this->createEmptyTarget();
        Filesystem::mkdir($target . '/DataTable');
        Filesystem::mkdir($target . '/DataTable/Filter');
        Filesystem::mkdir($target . '/DataTable/Renderer');
        Filesystem::mkdir($target . '/DataTable/Renderer/Xml');
        Filesystem::mkdir($target . '/DataTable/Row');

        file_put_contents($target . '/DataTable/Renderer/Console.php', '');
        file_put_contents($target . '/DataTable/Renderer/Csv.php', '');
        file_put_contents($target . '/DataTable/Renderer/Html.php', '');
        file_put_contents($target . '/DataTable/Renderer/Json.php', '');
        file_put_contents($target . '/DataTable/Renderer/Php.php', '');
        file_put_contents($target . '/DataTable/Renderer/Rss.php', '');
        file_put_contents($target . '/DataTable/Renderer/Tsv.php', '');
        file_put_contents($target . '/DataTable/Renderer/Xml.php', '');
        file_put_contents($target . '/DataTable/Renderer/Xml/Other.php', '');

        file_put_contents($target . '/DataTable/Row/DataTableSummaryRow.php', '');

        file_put_contents($target . '/DataTable/BaseFilter.php', '');
        file_put_contents($target . '/DataTable/Bridges.php', '');
        file_put_contents($target . '/DataTable/DataTableInterface.php', '');
        file_put_contents($target . '/DataTable/Manager.php', '');
        file_put_contents($target . '/DataTable/Map.php', '');
        file_put_contents($target . '/DataTable/Renderer.php', '');
        file_put_contents($target . '/DataTable/Row.php', '');
        file_put_contents($target . '/DataTable/Simple.php', '');

        return $target;
    }

    private function createEmptySource()
    {
        Filesystem::mkdir($this->testPath . '/source');

        return $this->testPath . '/source';
    }

    private function createEmptyTarget()
    {
        Filesystem::mkdir($this->testPath . '/target');

        return $this->testPath . '/target';
    }

    public function test_getFileSize_ZeroSize()
    {
        File::setFileSize(0);

        $size = Filesystem::getFileSize(__FILE__);
        $this->assertEquals(0, $size);

        $size = Filesystem::getFileSize(__FILE__, 'KB');
        $this->assertEquals(0, $size);

        $size = Filesystem::getFileSize(__FILE__, 'MB');
        $this->assertEquals(0, $size);

        $size = Filesystem::getFileSize(__FILE__, 'GB');
        $this->assertEquals(0, $size);

        $size = Filesystem::getFileSize(__FILE__, 'TB');
        $this->assertEquals(0, $size);
    }

    public function test_getFileSize_LowSize()
    {
        File::setFileSize(1024);

        $size = Filesystem::getFileSize(__FILE__);
        $this->assertEquals(1024, $size);

        $size = Filesystem::getFileSize(__FILE__, 'KB');
        $this->assertEquals(1, $size);

        $size = Filesystem::getFileSize(__FILE__, 'MB');
        $this->assertGreaterThanOrEqual(0.0009, $size);
        $this->assertLessThanOrEqual(0.0011, $size);

        $size = Filesystem::getFileSize(__FILE__, 'GB');
        $this->assertGreaterThanOrEqual(0.0000009, $size);
        $this->assertLessThanOrEqual(0.0000011, $size);

        $size = Filesystem::getFileSize(__FILE__, 'TB');
        $this->assertGreaterThanOrEqual(0.0000000009, $size);
        $this->assertLessThanOrEqual(0.0000000011, $size);
    }

    public function test_getFileSize_HighSize()
    {
        File::setFileSize(1073741824);

        $size = Filesystem::getFileSize(__FILE__, 'B');
        $this->assertEquals(1073741824, $size);

        $size = Filesystem::getFileSize(__FILE__, 'KB');
        $this->assertEquals(1048576, $size);

        $size = Filesystem::getFileSize(__FILE__, 'MB');
        $this->assertEquals(1024, $size);

        $size = Filesystem::getFileSize(__FILE__, 'GB');
        $this->assertEquals(1, $size);

        $size = Filesystem::getFileSize(__FILE__, 'TB');
        $this->assertGreaterThanOrEqual(0.0009, $size);
        $this->assertLessThanOrEqual(0.0011, $size);
    }

    public function test_getFileSize_ShouldRecognizeLowerUnits()
    {
        File::setFileSize(1073741824);

        $size = Filesystem::getFileSize(__FILE__, 'b');
        $this->assertEquals(1073741824, $size);

        $size = Filesystem::getFileSize(__FILE__, 'kb');
        $this->assertEquals(1048576, $size);

        $size = Filesystem::getFileSize(__FILE__, 'mB');
        $this->assertEquals(1024, $size);

        $size = Filesystem::getFileSize(__FILE__, 'Gb');
        $this->assertEquals(1, $size);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid unit given
     */
    public function test_getFileSize_ShouldThrowException_IfInvalidUnit()
    {
        Filesystem::getFileSize(__FILE__, 'iV');
    }

    public function test_getFileSize_ShouldReturnNull_IfFileDoesNotExists()
    {
        File::setFileExists(false);
        $size = Filesystem::getFileSize(__FILE__);

        $this->assertNull($size);
    }

}