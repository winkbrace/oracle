<?php namespace Oracle\Output;

use Oracle\Result\Result;

/**
 * Class Invert
 * @package Oracle\Output
 *
 * This class is responsible for inverting the result set of the query
 * This means swapping the rows and columns.
 */
class Inverter
{
    /** @var Result */
    protected $result;
    /** @var array */
    protected $columnNames;

    /**
     * @param Result $result
     */
    public function __construct(Result $result)
    {
        $this->result = $result;
        $this->columnNames = array_keys($this->result[0]);
    }

    /**
     * return array with columns as rows and vice versa
     * @return array inverted result
     */
    public function invert()
    {
        // create first column with all column names as row name
        $output = array();
        foreach ($this->columnNames as $column)
            $output[]['Field'] = $column;

        // add the inverted values in the above created sub-arrays
        foreach ($this->result as $r => $row)
        {
            $c = 0;
            foreach ($row as $field)
            {
                $output[$c++]['Record'.($r + 1)] = $field;
            }
        }

        return $output;
    }
}
