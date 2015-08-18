<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Unit\DataTable\Filter;

use Piwik\DataTable\Filter\Sort;
use Piwik\DataTable;
use Piwik\DataTable\Row;

/**
 * @group DataTableTest
 */
class DataTable_Filter_SortTest extends \PHPUnit_Framework_TestCase
{

    public function testNormalSortDescending()
    {
        $table = new DataTable();
        $table->addRowsFromArray(array(
                                      array(Row::COLUMNS => array('label' => 'ask', 'count' => 100)),
                                      array(Row::COLUMNS => array('label' => 'nintendo', 'count' => 0)),
                                      array(Row::COLUMNS => array('label' => 'yahoo', 'count' => 10)
                                      )));
        $filter = new Sort($table, 'count', 'desc');
        $filter->filter($table);
        $expectedOrder = array('ask', 'yahoo', 'nintendo');
        $this->assertEquals($expectedOrder, $table->getColumn('label'));
    }


    public function testNormalSortAscending()
    {
        $table = new DataTable();
        $table->addRowsFromArray(array(
                                      array(Row::COLUMNS => array('label' => 'ask', 'count' => 100.5)),
                                      array(Row::COLUMNS => array('label' => 'nintendo', 'count' => 0.5)),
                                      array(Row::COLUMNS => array('label' => 'yahoo', 'count' => 10.5)
                                      )));
        $filter = new Sort($table, 'count', 'asc');
        $filter->filter($table);
        $expectedOrder = array('nintendo', 'yahoo', 'ask');
        $this->assertEquals($expectedOrder, $table->getColumn('label'));
    }


    public function testMissingColumnValuesShouldAppearLastAfterSortAsc()
    {
        $table = new DataTable();
        $table->addRowsFromArray(array(
                                      array(Row::COLUMNS => array('label' => 'nintendo', 'count' => 1)),
                                      array(Row::COLUMNS => array('label' => 'nocolumn')),
                                      array(Row::COLUMNS => array('label' => 'nocolumnbis')),
                                      array(Row::COLUMNS => array('label' => 'ask', 'count' => 2)),
                                      array(Row::COLUMNS => array('label' => 'amazing')),
                                      DataTable::ID_SUMMARY_ROW => array(Row::COLUMNS => array('label' => 'summary', 'count' => 10)
                                      )));
        $filter = new Sort($table, 'count', 'asc');
        $filter->filter($table);
        $expectedOrder = array('nintendo', 'ask', 'nocolumnbis', 'nocolumn', 'amazing', 'summary');
        $this->assertEquals($expectedOrder, $table->getColumn('label'));
    }


    public function testMissingColumnValuesShouldAppearLastAfterSortDesc()
    {
        $table = new DataTable();
        $table->addRowsFromArray(array(
                                      array(Row::COLUMNS => array('label' => 'nintendo', 'count' => 1)),
                                      array(Row::COLUMNS => array('label' => 'ask', 'count' => 2)),
                                      array(Row::COLUMNS => array('label' => 'amazing')),
                                      DataTable::ID_SUMMARY_ROW => array(Row::COLUMNS => array('label' => 'summary', 'count' => 10)
                                      )));
        $filter = new Sort($table, 'count', 'desc');
        $filter->filter($table);
        $expectedOrder = array('ask', 'nintendo', 'amazing', 'summary');
        $this->assertEquals($expectedOrder, $table->getColumn('label'));
    }

    /**
     * Test to sort by label
     *
     * @group Core
     */
    public function testFilterSortString()
    {
        $idcol = Row::COLUMNS;
        $table = new DataTable();
        $rows = array(
            array($idcol => array('label' => 'google')), //0
            array($idcol => array('label' => 'ask')), //1
            array($idcol => array('label' => 'piwik')), //2
            array($idcol => array('label' => 'yahoo')), //3
            array($idcol => array('label' => 'amazon')), //4
            array($idcol => array('label' => '238975247578949')), //5
            array($idcol => array('label' => 'Q*(%&*("$&%*(&"$*")"))')) //6
        );
        $table->addRowsFromArray($rows);
        $expectedtable = new DataTable();
        $rows = array(
            array($idcol => array('label' => '238975247578949')), //5
            array($idcol => array('label' => 'amazon')), //4
            array($idcol => array('label' => 'ask')), //1
            array($idcol => array('label' => 'google')), //0
            array($idcol => array('label' => 'piwik')), //2
            array($idcol => array('label' => 'Q*(%&*("$&%*(&"$*")"))')), //6
            array($idcol => array('label' => 'yahoo')) //3
        );
        $expectedtable->addRowsFromArray($rows);
        $expectedtableReverse = new DataTable();
        $expectedtableReverse->addRowsFromArray(array_reverse($rows));

        $filter = new Sort($table, 'label', 'asc');
        $filter->filter($table);
        $this->assertTrue(DataTable::isEqual($expectedtable, $table));

        $filter = new Sort($table, 'label', 'desc');
        $filter->filter($table);
        $this->assertTrue(DataTable::isEqual($table, $expectedtableReverse));
    }

    /**
     * Test to sort by visit
     *
     * @group Core
     */
    public function testFilterSortNumeric()
    {
        $idcol = Row::COLUMNS;
        $table = new DataTable();
        $rows = array(
            array($idcol => array('label' => 'google', 'nb_visits' => 897)), //0
            array($idcol => array('label' => 'ask', 'nb_visits' => -152)), //1
            array($idcol => array('label' => 'piwik', 'nb_visits' => 1.5)), //2
            array($idcol => array('label' => 'yahoo', 'nb_visits' => 154)), //3
            array($idcol => array('label' => 'amazon', 'nb_visits' => 30)), //4
            array($idcol => array('label' => '238949', 'nb_visits' => 0)), //5
            array($idcol => array('label' => 'Q*(%&*', 'nb_visits' => 1)) //6
        );
        $table->addRowsFromArray($rows);
        $expectedtable = new DataTable();
        $rows = array(
            array($idcol => array('label' => 'ask', 'nb_visits' => -152)), //1
            array($idcol => array('label' => '238949', 'nb_visits' => 0)), //5
            array($idcol => array('label' => 'Q*(%&*', 'nb_visits' => 1)), //6
            array($idcol => array('label' => 'piwik', 'nb_visits' => 1.5)), //2
            array($idcol => array('label' => 'amazon', 'nb_visits' => 30)), //4
            array($idcol => array('label' => 'yahoo', 'nb_visits' => 154)), //3
            array($idcol => array('label' => 'google', 'nb_visits' => 897)) //0
        );
        $expectedtable->addRowsFromArray($rows);
        $expectedtableReverse = new DataTable();
        $expectedtableReverse->addRowsFromArray(array_reverse($rows));

        $filter = new Sort($table, 'nb_visits', 'asc');
        $filter->filter($table);
        $this->assertTrue(DataTable::isEqual($table, $expectedtable));

        $filter = new Sort($table, 'nb_visits', 'desc');
        $filter->filter($table);
        $this->assertTrue(DataTable::isEqual($table, $expectedtableReverse));
    }

    public function test_sortingArrayValues_doesNotError()
    {
        $table = new DataTable();
        $table->addRowsFromArray(array(
            array(Row::COLUMNS => array('label' => 'ask', 'count_array' => array(100, 1, 2) )),
            array(Row::COLUMNS => array('label' => 'nintendo', 'count_array' => array(0, 'hello'))),
            array(Row::COLUMNS => array('label' => 'yahoo', 'count_array' => array(10, 'test'))
            )));

        $tableOriginal = clone $table;

        $filter = new Sort($table, 'count_array', 'desc');
        $filter->filter($table);
        $this->assertTrue(DataTable::isEqual($tableOriginal, $table));
    }
}
