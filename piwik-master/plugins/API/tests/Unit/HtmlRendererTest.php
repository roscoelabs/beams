<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\API\tests\Unit;

use Piwik\DataTable;
use Piwik\Plugins\API\Renderer\Html;

/**
 * @group Plugin
 * @group API
 */
class HtmlRendererTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Html
     */
    private $builder;

    public function setUp()
    {
        $this->builder = $this->makeBuilder(array('method' => 'MultiSites_getAll'));
        DataTable\Manager::getInstance()->deleteAll();
    }

    public function test_renderSuccess_shouldIncludeMessage()
    {
        $response = $this->builder->renderSuccess('ok');

        $this->assertEquals('Success:ok', $response);
    }

    public function test_renderException_shouldIncludeTheMessageAndNotExceptionMessage()
    {
        $response = $this->builder->renderException("The error message", new \Exception('The other message'));

        $this->assertEquals('The error message', $response);
    }

    public function test_renderException_shouldConvertNewLinesToBr()
    {
        $response = $this->builder->renderException("The\nerror\nmessage", new \Exception('The other message'));

        $this->assertEquals('The<br />
error<br />
message', $response);
    }

    public function test_renderObject_shouldReturAnError()
    {
        $response = $this->builder->renderObject(new \stdClass());

        $this->assertEquals('The API cannot handle this data structure.', $response);
    }

    public function test_renderResource_shouldReturAnError()
    {
        $response = $this->builder->renderResource(new \stdClass());

        $this->assertEquals('The API cannot handle this data structure.', $response);
    }

    public function test_renderScalar_shouldReturnABooleanAsIntegerWrappedInTable()
    {
        $response = $this->builder->renderScalar(true);

        $this->assertEquals('<table id="MultiSites_getAll" border="1">
<thead>
	<tr>
		<th>value</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>1</td>
	</tr>
</tbody>
</table>
', $response);
    }

    public function test_renderScalar_shouldReturnAnIntegerWrappedInTable()
    {
        $response = $this->builder->renderScalar(5);

        $this->assertEquals('<table id="MultiSites_getAll" border="1">
<thead>
	<tr>
		<th>value</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>5</td>
	</tr>
</tbody>
</table>
', $response);
    }

    public function test_renderScalar_shouldReturnAStringWrappedInValue()
    {
        $response = $this->builder->renderScalar('The Output');

        $this->assertEquals('<table id="MultiSites_getAll" border="1">
<thead>
	<tr>
		<th>value</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>The Output</td>
	</tr>
</tbody>
</table>
', $response);
    }

    public function test_renderScalar_shouldNotRemoveLineBreaks()
    {
        $response = $this->builder->renderScalar('The\nOutput');

        $this->assertEquals('<table id="MultiSites_getAll" border="1">
<thead>
	<tr>
		<th>value</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>The\nOutput</td>
	</tr>
</tbody>
</table>
', $response);
    }

    public function test_renderDataTable_shouldRenderABasicDataTable()
    {
        $dataTable = new DataTable();
        $dataTable->addRowFromSimpleArray(array('nb_visits' => 5, 'nb_random' => 10));

        $response = $this->builder->renderDataTable($dataTable);

        $this->assertEquals('<table id="MultiSites_getAll" border="1">
<thead>
	<tr>
		<th>nb_visits</th>
		<th>nb_random</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>5</td>
		<td>10</td>
	</tr>
</tbody>
</table>
', $response);
    }

    public function test_renderDataTable_shouldRenderSubtables()
    {
        $subtable = new DataTable();
        $subtable->addRowFromSimpleArray(array('nb_visits' => 2, 'nb_random' => 6));

        $dataTable = new DataTable();
        $dataTable->addRowFromSimpleArray(array('nb_visits' => 5, 'nb_random' => 10));
        $dataTable->getFirstRow()->setSubtable($subtable);

        $response = $this->builder->renderDataTable($dataTable);

        $this->assertEquals('<table id="MultiSites_getAll" border="1">
<thead>
	<tr>
		<th>nb_visits</th>
		<th>nb_random</th>
		<th>_idSubtable</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>5</td>
		<td>10</td>
		<td>1</td>
	</tr>
</tbody>
</table>
', $response);
    }

    public function test_renderDataTable_shouldRenderDataTableMaps()
    {
        $map = new DataTable\Map();

        $dataTable = new DataTable();
        $dataTable->addRowFromSimpleArray(array('nb_visits' => 5, 'nb_random' => 10));

        $dataTable2 = new DataTable();
        $dataTable2->addRowFromSimpleArray(array('nb_visits' => 3, 'nb_random' => 6));

        $map->addTable($dataTable, 'table1');
        $map->addTable($dataTable2, 'table2');

        $response = $this->builder->renderDataTable($map);

        $this->assertEquals('<table id="MultiSites_getAll" border="1">
<thead>
	<tr>
		<th>_defaultKeyName</th>
		<th>nb_visits</th>
		<th>nb_random</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>table1</td>
		<td>5</td>
		<td>10</td>
	</tr>
	<tr>
		<td>table2</td>
		<td>3</td>
		<td>6</td>
	</tr>
</tbody>
</table>
', $response);
    }

    public function test_renderDataTable_shouldRenderSimpleDataTable()
    {
        $dataTable = new DataTable\Simple();
        $dataTable->addRowsFromArray(array('nb_visits' => 3, 'nb_random' => 6));

        $response = $this->builder->renderDataTable($dataTable);

        $this->assertEquals('<table id="MultiSites_getAll" border="1">
<thead>
	<tr>
		<th>nb_visits</th>
		<th>nb_random</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>3</td>
		<td>6</td>
	</tr>
</tbody>
</table>
', $response);
    }

    public function test_renderArray_ShouldConvertSimpleArrayToJson()
    {
        $input = array(1, 2, 5, 'string', 10);

        $response = $this->builder->renderArray($input);

        $this->assertEquals('<table id="MultiSites_getAll" border="1">
<thead>
	<tr>
		<th>value</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>1</td>
	</tr>
	<tr>
		<td>2</td>
	</tr>
	<tr>
		<td>5</td>
	</tr>
	<tr>
		<td>string</td>
	</tr>
	<tr>
		<td>10</td>
	</tr>
</tbody>
</table>
', $response);
    }

    public function test_renderArray_ShouldRenderAnEmptyArray()
    {
        $response = $this->builder->renderArray(array());

        $this->assertEquals('<table id="MultiSites_getAll" border="1">
<thead>
	<tr>
	</tr>
</thead>
<tbody>
</tbody>
</table>
', $response);
    }

    public function test_renderArray_ShouldConvertAssociativeArrayToJson()
    {
        $input = array('nb_visits' => 6, 'nb_random' => 8);

        $response = $this->builder->renderArray($input);

        $this->assertEquals('<table id="MultiSites_getAll" border="1">
<thead>
	<tr>
		<th>nb_visits</th>
		<th>nb_random</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>6</td>
		<td>8</td>
	</tr>
</tbody>
</table>
', $response);
    }

    public function test_renderArray_ShouldConvertsIndexedAssociativeArrayToJson()
    {
        $input = array(
            array('nb_visits' => 6, 'nb_random' => 8),
            array('nb_visits' => 3, 'nb_random' => 4)
        );

        $response = $this->builder->renderArray($input);

        $this->assertEquals('<table id="MultiSites_getAll" border="1">
<thead>
	<tr>
		<th>nb_visits</th>
		<th>nb_random</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>6</td>
		<td>8</td>
	</tr>
	<tr>
		<td>3</td>
		<td>4</td>
	</tr>
</tbody>
</table>
', $response);
    }

    public function test_renderArray_ShouldConvertMultiDimensionalStandardArrayToJson()
    {
        $input = array("firstElement",
            array(
                "firstElement",
                "secondElement",
            ),
            "thirdElement");

        $actual = $this->builder->renderArray($input);
        $this->assertEquals('<table id="MultiSites_getAll" border="1">
<thead>
	<tr>
		<th>value</th>
		<th>1</th>
		<th>2</th>
	</tr>
</thead>
<tbody>
	<tr>
		<td>firstElement</td>
		<td>-</td>
		<td>-</td>
	</tr>
	<tr>
		<td>firstElement</td>
		<td>secondElement</td>
		<td>-</td>
	</tr>
	<tr>
		<td>-</td>
		<td>-</td>
		<td>thirdElement</td>
	</tr>
</tbody>
</table>
', $actual);
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

        $this->builder->renderArray($input);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Data structure returned is not convertible in the requested format
     */
    public function test_renderArray_ShouldConvertMultiDimensionalIndexArrayToJson()
    {
        $input = array(array("firstElement",
            array(
                "firstElement",
                "secondElement",
            ),
            "thirdElement"));

        $this->builder->renderArray($input);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Data structure returned is not convertible in the requested format
     */
    public function test_renderArray_ShouldConvertMultiDimensionalMixedArrayToJson()
    {
        $input = array(
            "firstElement" => "isFirst",
            array(
                "firstElement",
                "secondElement",
            ),
            "thirdElement" => array(
                "firstElement"  => "isFirst",
                "secondElement" => "isSecond",
            )
        );

        $this->builder->renderArray($input);
    }

    private function makeBuilder($request)
    {
        return new Html($request);
    }
}
