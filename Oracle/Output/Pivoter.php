<?php namespace Oracle\Output;

use Oracle\OracleException;
use Oracle\Result\Result;

/**
 * Class Pivot
 * @package Oracle\Output
 * This class is responsible for creating a pivoted result array
 */
class Pivoter
{
    /** @var string */
    const SEPARATOR = "[[SEPARATOR]]";  // string to separate the rowHeader values

    /** @var Result */
    protected $result;
    /** @var array */
    protected $pivot = array();
    /** @var string|array */
    protected $colHeadersField;
    /** @var array */
    protected $rowHeaders;
    /** @var string */
    protected $dataField;
    /** @var array */
    protected $rowIdentifiers = array();
    /** @var bool */
    protected $sortByFirstColumn = true;

    /**
     * @param Result $result
     */
    public function __construct(Result $result)
    {
        $this->result = $result;
    }

    /**
     * create a pivoted array of the query result
     *
     * @param string       $colHeadersField
     * @param string|array $rowHeaders
     * @param string       $dataField
     * @param bool         $showTotal
     * @throws \Oracle\OracleException
     * @return array $pivot
     */
    public function toPivot($colHeadersField, $rowHeaders, $dataField, $showTotal = true)
    {
        if (is_array($colHeadersField) || strpos($colHeadersField, ',') !== false)
            throw new OracleException('You cannot pivot on multiple column header fields');

        if (empty($this->result))
            return array();

        $this->initialize($colHeadersField, $rowHeaders, $dataField);

        foreach ($this->result as $row)
        {
            $rowIdentifier = $this->createRowIdentifier($row);
            $pivotColName = isset($row[$colHeadersField]) ? $row[$colHeadersField] : '_Onbekend';
            $this->sumDataPerRow($rowIdentifier, $row[$dataField]);
            $this->sumDataPerField($pivotColName, $rowIdentifier, $row[$dataField]);
        }

        $this->sortPivotOutput();

        return $this->explodeRowHeaders($showTotal);
    }

    /**
     * @param $colHeadersField
     * @param $rowHeaders
     * @param $dataField
     */
    protected function initialize($colHeadersField, $rowHeaders, $dataField)
    {
        if (! is_array($rowHeaders))
            $rowHeaders = explode(",", $rowHeaders);

        $this->colHeadersField = $colHeadersField;
        $this->rowHeaders      = $rowHeaders;
        $this->dataField       = $dataField;
        $this->rowIdentifiers  = array();
        $this->pivot      = array();
    }

    /**
     * Glue all row headers together as row identifier to be able to treat them as one field
     * @param array $row
     * @return string
     */
    protected function createRowIdentifier(array $row)
    {
        $rowFields = array();

        foreach ($this->rowHeaders as $rh)
            $rowFields[] = isset($row[$rh]) ? $row[$rh] : '';

        return implode(self::SEPARATOR, $rowFields);
    }

    /**
     * Collect all row values. (By putting them in the key, they are unique)
     * By adding the values, you will get the pivotted totals
     * @param string $rowIdentifier
     * @param int $int
     */
    protected function sumDataPerRow($rowIdentifier, $int)
    {
        if (isset($this->rowIdentifiers[$rowIdentifier]))
            $this->rowIdentifiers[$rowIdentifier] += $int;
        else
            $this->rowIdentifiers[$rowIdentifier] = $int;
    }

    /**
     * Add values per field for the output pivot array. Usually there will be only 1 value per field.
     * @param string $pivotColName
     * @param string $rowIdentifier
     * @param int $int
     */
    protected function sumDataPerField($pivotColName, $rowIdentifier, $int)
    {
        if (isset($this->pivot[$pivotColName][$rowIdentifier]))
            $this->pivot[$pivotColName][$rowIdentifier] += $int;
        else
            $this->pivot[$pivotColName][$rowIdentifier] = $int;
    }

    /**
     *
     */
    protected function sortPivotOutput()
    {
        ksort($this->pivot);
        if ($this->sortByFirstColumn)
        {
            ksort($this->rowIdentifiers);
        }
    }

    /**
     * explode row headers again and fill in empty values for missing fields
     * @param bool $showTotal
     * @return array
     */
    protected function explodeRowHeaders($showTotal)
    {
        $output = array();

        // loop nog een keer door alle data om voor elke rij en elke kolom een waarde (of een leeg veld) te hebben
        foreach ($this->rowIdentifiers as $rowIdentifier => $rowTotal)
        {
            $rowValues = explode(self::SEPARATOR, $rowIdentifier);

            // first columns contain the row names
            for ($i = 0; $i < count($this->rowHeaders); $i++)
                $output[$rowIdentifier][$this->rowHeaders[$i]] = $rowValues[$i];  // $rowHeaders en $rowValues are in the same order

            foreach ($this->pivot as $colName => $colValues)
                $output[$rowIdentifier][$colName] = isset($colValues[$rowIdentifier]) ? $colValues[$rowIdentifier] : '';

            // laat totalen zien
            if ($showTotal)
                $output[$rowIdentifier]['Total'] = $rowTotal;
        }

        return array_values($output);
    }

    /**
     * @param boolean $sortByFirstRow
     */
    public function setSortByFirstColumn($sortByFirstRow)
    {
        $this->sortByFirstColumn = $sortByFirstRow;
    }

}
