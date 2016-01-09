<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\NQL;


use Trinity\Bundle\SearchBundle\Exception\SyntaxErrorException;


/**
 * Class Select
 * @package Trinity\Bundle\SearchBundle\NQL
 */
class Select
{
    private $columns = array();


    private function __construct()
    {
    }


    /**
     * Parse SELECT string
     * @param string $str
     * @return Select
     * @throws SyntaxErrorException
     */
    public static function parse($str = "")
    {
        $selection = new Select();

        $columns = preg_split("/,/", $str);

        foreach ($columns as &$column) {
            $column = Column::parse($column);
        }

        $selection->columns = $columns;

        return $selection;
    }


    /**
     * @return Select
     */
    public static function getBlank()
    {
        return new Select();
    }


    /**
     * @return Column[]
     */
    public function getColumns()
    {
        return $this->columns;
    }
}