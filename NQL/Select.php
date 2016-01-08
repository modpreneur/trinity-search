<?php

namespace Trinity\SearchBundle\NQL;


use Trinity\SearchBundle\Exception\SyntaxErrorException;

class Select
{
    private $columns = array();

    private function __construct()
    {
    }

    /**
     * @return Column[]
     */
    public function getColumns() {
        return $this->columns;
    }

    /**
     * Parse SELECT string
     * @param string $str
     * @return Select
     * @throws SyntaxErrorException
     */
    public static function parse($str = "") {
        $selection = new Select();

        $columns = preg_split("/,/", $str);

        foreach($columns as &$column) {
            $column = Column::parse($column);
        }

        $selection->columns = $columns;

        return $selection;
    }

    public static function getBlank() {
        return new Select();
    }
}