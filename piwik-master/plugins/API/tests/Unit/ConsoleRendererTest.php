<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\API\tests\Unit;

use Piwik\DataTable;
use Piwik\Plugins\API\Renderer\Console;

/**
 * @group Plugin
 * @group API
 */
class ConsoleRendererTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Console
     */
    private $builder;

    public function setUp()
    {
        $this->builder = $this->makeBuilder(array());
        DataTable\Manager::getInstance()->deleteAll();
    }

    public function test_renderSuccess_shouldAlwaysReturnTrueAndIgnoreMessage()
    {
        $response = $this->builder->renderSuccess('ok');

        $this->assertEquals('Success:ok', $response);
    }

    public function test_renderException_shouldThrowTheException()
    {
        $response = $this->builder->renderException('This message should be used', new \BadMethodCallException('The other message'));

        $this->assertEquals('Error: This message should be used', $response);
    }

    public function test_renderScalar_shouldReturnTheSameValue()
    {
        $response = $this->builder->renderScalar(true);
        $this->assertSame("- 1 ['0' => 1] [] [idsubtable = ]<br />
", $response);

        $response = $this->builder->renderScalar(5);
        $this->assertSame("- 1 ['0' => 5] [] [idsubtable = ]<br />
", $response);

        $response = $this->builder->renderScalar('string');
        $this->assertSame("- 1 ['0' => 'string'] [] [idsubtable = ]<br />
", $response);
    }

    public function test_renderObject_shouldReturAnError()
    {
        $response = $this->builder->renderObject(new \stdClass());

        $this->assertEquals('Error: The API cannot handle this data structure.', $response);
    }

    public function test_renderResource_shouldReturAnError()
    {
        $response = $this->builder->renderResource(new \stdClass());

        $this->assertEquals('Error: The API cannot handle this data structure.', $response);
    }

    public function test_renderDataTable_shouldReturnResult()
    {
        $dataTable = new DataTable();
        $dataTable->addRowFromSimpleArray(array('nb_visits' => 5, 'nb_random' => 10));

        $response = $this->builder->renderDataTable($dataTable);

        $this->assertSame("- 1 ['nb_visits' => 5, 'nb_random' => 10] [] [idsubtable = ]<br />
", $response);
    }

    public function test_renderArray_ShouldReturnConsoleResult()
    {
        $input = array(1, 2, 5, 'string', 10);

        $response = $this->builder->renderArray($input);

        $this->assertSame("- 1 ['0' => 1] [] [idsubtable = ]<br />
- 2 ['0' => 2] [] [idsubtable = ]<br />
- 3 ['0' => 5] [] [idsubtable = ]<br />
- 4 ['0' => 'string'] [] [idsubtable = ]<br />
- 5 ['0' => 10] [] [idsubtable = ]<br />
", $response);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Data structure returned is not convertible in the requested format
     */
    public function test_renderArray_ShouldConvertMultiDimensionalAssociativeArrayToJson()
    {
        $input = array(
            "firstElement"  => "isFirst",
            "secondElement" => array(
                "firstElement"  => "isFirst",
                "secondElement" => "isSecond",
            ),
            "thirdElement"  => "isThird");

        $actual = $this->builder->renderArray($input);
        $this->assertSame($input, $actual);
    }

    private function makeBuilder($request)
    {
        return new Console($request);
    }
}
