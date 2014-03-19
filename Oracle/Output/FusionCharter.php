<?php namespace Oracle\Output;

use Oracle\Result\Result;

/**
 * Class FusionCharts
 * @package Oracle\Output
 *
 * This class is responsible for creating the arrays FusionCharts needs as input
 */
class FusionCharter
{
    /** @var Result */
    protected $result;

    /**
     * @param Result $result
     */
    public function __construct(Result $result)
    {
        $this->result = $result;
    }

    /**
     * Generate Fusion Charts $data array, the $xCategories array and the query result array.
     * Deze functie maakt een lijn van elke kolom gegeven in yCategoryColumns.
     *
     * @param string $xCategoriesColumn column containing the x categories
     * @param array $yCategoriesColumns list of columns that are the y categories
     *
     * @return array($data, $xCategories, $result)
     */
    public function toFusionChartsArrayX($xCategoriesColumn, array $yCategoriesColumns)
    {
        // collect data into arrays FusionCharts can use
        // als je arrays wilt gebruiken moet je dat op de volgende wijze doen:
        // 0. Input Arrays moeten numeriek zijn.
        // 1. A single dimensional array storing the xCategory names
        // 2. A multi dimensional array waarvan de 1e kolom de groep naam is en de 2e kolom de evt paramaters en de data begint bij kolom 3

        // categories
        $xCategories = array();
        foreach ($this->result as $row)
            $xCategories[] = $row[$xCategoriesColumn];

        // data
        $data = array();
        $i = 0;
        foreach ($yCategoriesColumns as $cat)
        {
            $data[$i][] = ucfirst(strtolower($cat));
            $data[$i][] = '';
            foreach ($this->result as $row)
            {
                $data[$i][] = $row[$cat];
            }

            $i++;
        }

        return array($data, $xCategories);
    }


    /**
     * Generate Fusion Charts $data array, the $xCategories array and the query result array.
     * Deze functie maakt een lijn voor elke unieke waarde in kolom $yCategoryColumn.
     *
     * @param string $xCategoriesColumn column containing the x categories
     * @param string $yCategoriesColumn column containging the y categories
     * @param string $valueColumn       column containing the value
     * @return array
     */
    public function toFusionChartsArrayY($xCategoriesColumn, $yCategoriesColumn, $valueColumn)
    {
        // collect data into arrays FusionCharts can use
        // als je arrays wilt gebruiken moet je dat op de volgende wijze doen:
        // 0. Input Arrays moeten numeriek zijn.
        // 1. A single dimensional array storing the xCategory names
        // 2. A multi dimensional array waarvan de 1e kolom de groep naam is en de 2e kolom de evt paramaters en de data begint bij kolom 3

        // categories
        $xCategories = $yCategories = array();
        foreach ($this->result as $row)
        {
            $xCategories[$row[$xCategoriesColumn]] = 1; // truukje: ik overschrijf telkens een eerder gebruikte key, dus op deze manier meteen uniek
            $yCategories[ucfirst(strtolower($row[$yCategoriesColumn]))][$row[$xCategoriesColumn]] = $row[$valueColumn];
        }

        $xCategories = array_keys($xCategories);

        // data
        $data = array();
        $i = 0;
        foreach ($yCategories as $yCat => $values)
        {
            $data[$i][] = $yCat; // groep naam
            $data[$i][] = '';    // evt parameters

            foreach ($xCategories as $xCat)
                $data[$i][] = isset($values[$xCat]) ? $values[$xCat] : ''; // op deze manier (ipv loop over $values) juiste koppeling bij lege velden
            $i++;
        }

        return array($data, $xCategories);
    }

    /**
     * Generate Fusion Charts $data array, the $xCategories array and the query result array.
     * Use this FusionCharts function if you have multiple columns per row you want to create a line of
     * A line will be created for the unique combination of yCategoriesColumns with each valueColumn
     *
     * @param string $xCategoriesColumn
     * @param array $yCategoriesColumns
     * @param array $valueColumns
     *
     * @return array(data, xCategories)
     */
    public function toFusionChartsArrayZ($xCategoriesColumn, array $yCategoriesColumns, array $valueColumns)
    {
        // collect data into arrays FusionCharts can use
        // als je arrays wilt gebruiken moet je dat op de volgende wijze doen:
        // 0. Input Arrays moeten numeriek zijn.
        // 1. A single dimensional array storing the xCategory names
        // 2. A multi dimensional array waarvan de 1e kolom de groep naam is en de 2e kolom de evt paramaters en de data begint bij kolom 3

        // because we don't know the y categories at once, we have to create them while looping through the result
        $xCategories = array();
        $yCategories = array();
        foreach ($this->result as $row)
        {
            // collect unique x categories
            $xCategories[$row[$xCategoriesColumn]] = 1;

            // collect values in yCategories
            foreach ($valueColumns as $col)
            {
                // create category. first collect the values of the ycategories and the value column name in an array and then implode on space
                $cat = array();
                foreach ($yCategoriesColumns as $ycol)
                    $cat[] = $row[$ycol];
                $cat[] = initcap($col);
                $ycat = implode(' ', $cat);

                // collect value in categories array
                $yCategories[$ycat][] = $row[$col];
            }
        }

        // create actual xCategories and data arrays
        $xCategories = array_keys($xCategories);

        $data = array();
        $i = 0;
        foreach ($yCategories as $cat => $amounts)
        {
            $data[$i][] = $cat;
            $data[$i][] = '';
            foreach ($amounts as $amount)
            {
                $data[$i][] = $amount;
            }

            $i++;
        }

        return array($data, $xCategories);
    }
}
