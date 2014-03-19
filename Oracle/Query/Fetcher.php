<?php namespace Oracle\Query;

use Oracle\Result\Result;
use Oracle\Result\Row;

/**
 * This class is responsible for fetching the records in the result set
 */
class Fetcher
{
    const FETCH_ASSOC = OCI_ASSOC;  // fetch row as associative array
    const FETCH_NUM   = OCI_NUM;    // fetch row as numeric array
    const FETCH_BOTH  = OCI_BOTH;   // fetch row as both associative and numeric array

    /** @var Statement */
    protected $statement;
    /** @var Result */
    protected $result;
    /** @var int */
    protected $numFields;
    /** @var array */
    protected $columnNames = array();
    /** @var array */
    protected $columnTypes = array();
    /** @var string */
    protected $dateFormat = 'd-m-Y H:i:s';
    /** @var int */
    protected $numRows;
    /** @var string */
    protected $firstValue = false;
    /** @var array */
    protected $fetchColumnResult;  // this array holds the results fetched per column instead of per row


    /**
     * @param Statement $statement
     */
    public function __construct(Statement $statement)
    {
        $this->statement = $statement;
        $this->statement->execute(Executor::NO_COMMIT); // no reason to auto-commit after SELECT statements

        // set attributes
        $this->numFields = oci_num_fields($this->statement->getResource());
        $this->setColumnNames();
        $this->setColumnTypes();
    }

    /**
     * fetch all rows of the query
     * @param int $type
     * @return Result
     */
    public function fetchAll($type = self::FETCH_ASSOC)
    {
        if (empty($this->result))
            $this->fetchResult($type);

        return $this->result;
    }

    /**
     * fetch complete result set
     * @param int $type
     */
    protected function fetchResult($type = self::FETCH_ASSOC)
    {
        $this->result = new Result();

        while ($row = $this->fetch($type))
        {
            $this->result->push($row);
        }
    }

    /**
     * Use this function if you are interested in the first value only
     * For example count(*) or max(id)
     * Uses first field, or when specified the given column
     * Note: per object only 1 first value is allowed. If you need more fields from the first record, use fetch()
     *
     * @param string $column
     * @return string|false
     */
    public function fetchFirstValue($column = null)
    {
        // we init firstValue as false, because that is a value we know hasn't been returned from the database.
        // this way we will never run into the situation that we already fetched the first value and overwrite it
        if ($this->firstValue === false)
        {
            if ($row = $this->fetch(self::FETCH_BOTH))
            {
                $column = $column ?: 0;
                $this->firstValue = $row[$column];
            }
        }

        return $this->firstValue;
    }

    /**
     * return the first 2 columns of the result of the query as key => value pairs in a one-dimensional array
     * @param string $keyCol
     * @param string $valCol
     * @return array
     */
    public function fetchArray($keyCol = null, $valCol = null)
    {
        $result = $this->fetchAll(self::FETCH_BOTH);

        // use either the given key and value columns or the first 2 columns in the query
        $keyCol = empty($keyCol) ? 0 : strtoupper($keyCol);
        $valCol = empty($valCol) ? 1 : strtoupper($valCol);

        return $result->lists($valCol, $keyCol);
    }

    /**
     * return the one column in the result set as a numeric array
     */
    public function fetchColumn($columnName = null)
    {
        if (empty($this->fetchColumnResult))
            oci_fetch_all($this->statement->getResource(), $this->fetchColumnResult);

        $columnName = $columnName ?: array_shift($this->getColumnNames());
        $columnName = strtoupper($columnName);

        if (empty($this->fetchColumnResult[$columnName]))
            return array();

        return $this->fetchColumnResult[$columnName];
    }

    /**
     * fetch the next row
     * @param int $type
     * @return Row
     */
    public function fetch($type = self::FETCH_ASSOC)
    {
        $flag = $type + OCI_RETURN_NULLS;;
        if ($this->hasLobs())
            $flag += OCI_RETURN_LOBS;

        $fetched = oci_fetch_array($this->statement->getResource(), $flag);
        if (! $fetched)
            return false;

        return $this->formatDates(new Row($fetched), $type);
    }

    /**
     * check if the selection contains LOBs
     * This functions can only be called after the query is executed
     */
    protected function hasLobs()
    {
        foreach ($this->getColumnTypes() as $type)
        {
            if (in_array($type, array('CLOB','BLOB','LONG')))
                return true;
        }
        return false;
    }

    /**
     * This function inserts fields with NULL value in the row array so we can always
     * use $row['NAME'] or $row[int] to fetch the row without having to check if it exists,
     * because by default if the field is empty, oci8 will not return it for that row. ><
     *
     * @param Row $row
     * @param int $type
     * @throws \InvalidArgumentException
     * @return array|false $return
     */
    protected function formatDates(Row $row, $type)
    {
        // end of collection. we return false here so we can still use this in the while($row = fetch) loop
        if (empty($row))
        {
            return false;
        }

        switch ($type)
        {
            case Fetcher::FETCH_ASSOC:
                $dateColumns = array_keys($this->getColumnTypes(), 'DATE');
                break;
            case Fetcher::FETCH_NUM:
                $dateColumns = array_keys(array_values($this->getColumnTypes()), 'DATE');
                break;
            case Fetcher::FETCH_BOTH:
                $dateColumns = array_keys($this->getColumnTypes(), 'DATE') + array_keys(array_values($this->getColumnTypes()), 'DATE');
                break;
            default:
                throw new \InvalidArgumentException('Invalid fetch type "'.$type.'" given to Result->formatDates (has to be integer).');
        }

        if (! empty($dateColumns))
        {
            // only format the date columns
            foreach ($dateColumns as $dateColumn)
            {
                $row[$dateColumn] = $this->formatDate($row[$dateColumn]);
            }
        }

        return $row;
    }

    /**
     * format date field to the specified format
     * @param mixed $field
     * @return mixed
     */
    protected function formatDate($field)
    {
        if (empty($field))
        {
            return $field;
        }

        $formatted = date($this->dateFormat, strtotime($field));
        return str_replace(' 00:00:00', '', $formatted); // remove time part if not set
    }

    /**
     * @return string $date_format
     */
    public function getDateFormat()
    {
        return $this->dateFormat;
    }

    /**
     * set the default date format. For Excel we might want to use something not interpretable like mm-dd-yy
     * @param string $dateFormat
     */
    public function setDateFormat($dateFormat)
    {
        $this->dateFormat = $dateFormat;
    }

    /**
     * @return array
     */
    public function getColumnNames()
    {
        return $this->columnNames;
    }

    /**
     * Check if a column exists in the result set.
     * @param string $columnName the column name
     * @return boolean
     */
    public function hasColumn($columnName)
    {
        return in_array($columnName, $this->getColumnNames());
    }

    /**
     * store the column names of the query in private array
     */
    protected function setColumnNames()
    {
        if (empty($this->columnNames))
        {
            for ($i = 1; $i <= $this->numFields; $i++)
            {
                $col = oci_field_name($this->statement->getResource(), $i);
                $this->columnNames[] = $col; // store column names in array
            }
        }
    }

    /**
     * Returns a column's type
     *
     * @param null $index
     * @internal param mixed $column - a column index (0 based!) or column name (preferred since this is how they are stored)
     * @return string|boolean - false if column index/name doesn't exist or the column type otherwise
     */
    public function getColumnType($index = null)
    {
        // numeric index, find column by index
        if (is_numeric($index))
        {
            $typesByIndex = array_values($this->columnTypes);
            return array_key_exists($index, $typesByIndex) ? $typesByIndex[$index] : false;
        }
        // string, find column by name
        else
        {
            return array_key_exists($index, $this->columnTypes) ? $this->columnTypes[$index] : false;
        }
    }

    /**
     * @return array
     */
    public function getColumnTypes()
    {
        return $this->columnTypes;
    }

    /**
     * store the column types of the query
     */
    protected function setColumnTypes()
    {
        if (empty($this->columnTypes))
        {
            $columns = $this->getColumnNames();
            foreach ($columns as $column)
            {
                $this->columnTypes[$column] = oci_field_type($this->statement->getResource(), $column);
            }
        }
    }

    /**
     * get the amount of affected rows in the statement
     * @return int $num_rows
     */
    public function getNumRows()
    {
        if (empty($this->numRows));
            $this->setNumRows();

        return $this->numRows;
    }

    protected function setNumRows()
    {
        // oci_num_rows will return the number of fetched rows so far for a SELECT statement
        if ($this->statement->getStatementType() != 'SELECT')
        {
            $this->numRows = oci_num_rows($this->statement->getResource());
        }
        elseif (! empty($this->result))
        {
            $this->numRows = $this->result->count();
        }
        else
        {
            $sql = "select count(*) as num_rows
                    from
                    (
                        ".$this->statement->getSql()."
                    )";
            $parsed = oci_parse($this->statement->getConnectionResource(), $sql);

            if (! empty($this->bindVariables))
            {
                foreach ($this->bindVariables as $bind => $name)
                    oci_bind_by_name($parsed, $bind, $name);
            }

            oci_define_by_name($parsed, "NUM_ROWS", $numRows);
            oci_execute($parsed);
            oci_fetch($parsed);
            oci_free_statement($parsed);

            $this->numRows = (int) $numRows;
        }
    }
}
