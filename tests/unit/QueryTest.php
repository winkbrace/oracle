<?php

use \QueryAdapter as Query;

// these tests are copied from the old Query class tests. They should all still pass

class QueryTest extends PHPUnit_Framework_TestCase
{
    /**
     * test creation of row object with public upper- and lowercase attributes
     */
    public function testRowWithIndifferentAccess()
    {
        $query = new Query('select 2 + 4 as foo from dual', 'T2');

        $rows = $query->fetchAll(Query::FETCH_INDIFFERENT);
        $row = $rows->first();

        $this->assertEquals($row->foo, 6);
        $this->assertEquals($row->FOO, 6);
        $this->assertEquals($row->Foo, 6);
    }

    /**
     * non existing attribute will be returned as null
     */
    public function testRowWithIndifferentAccessOnNotExistingAttribute()
    {
        $query = new Query('select 2 + 4 as foo from dual', 'T2');
        $rows = $query->fetchAll(Query::FETCH_INDIFFERENT);
        $row = $rows->first();
        $this->assertNull($row->bar);
    }

    /**
     * test creating 1 dimensional array of query
     */
    public function testFetchArray()
    {
        $query = new Query("select at.action_type_id
                            ,      at.short_name
                            ,      at.name
                            from   cm_action_types at
                            where  at.action_type_id between 1 and 3
                            order by 1", 'CM');

        // test named creation
        $array = $query->fetchArray('action_type_id', 'name');

        $expected = array(1 => 'Direct Mail', 2 => 'Telemarketing Inbound', 3 => 'Telemarketing Outbound');
        $this->assertEquals($expected, $array);

        // test unnamed creation (using first 2 columns)
        $array = $query->fetchArray();

        $expected = array(1 => 'DM', 2 => 'TM in', 3 => 'TM out');
        $this->assertEquals($expected, $array);

    }

    /**
     * test creating array of values from 1 column
     */
    public function testFetchColumn()
    {
        $query = new Query("select 1 nr from dual union all select 2 from dual union all select 3 from dual");

        // test named
        $array = $query->fetchColumn('nr');
        $this->assertEquals(array(1,2,3), $array);

        // test unnamed
        $array = $query->fetchColumn();
        $this->assertEquals(array(1,2,3), $array);
    }

    /**
     * test setting of column types
     */
    public function testColumnTypes()
    {
        $query = new Query("select sysdate one, 2 two from dual");
        $query->execute();
        $this->assertEquals(array('ONE' => 'DATE', 'TWO' => 'NUMBER'), $query->getColumnType());
    }

    public function testBindsUsingQueryAdapter()
    {
        $query = new Query("select 'foo' one from dual where 1 = :var");
        $binds = array(':var' => 1);

        $query->bind($binds);
        $this->assertEquals($binds, $query->getBindVariables());
    }
}
