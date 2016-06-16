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
    /** @var Column[]  */
    private $columns = [];


    /**
     * Select constructor.
     */
    private function __construct()
    {
    }


    /**
     * Parse SELECT string
     * @param string $str
     * @return Select
     * @throws SyntaxErrorException
     */
    public static function parse($str = '') : Select
    {
        $selection = new Select();

        $columns = explode(',', $str);

        foreach ($columns as &$column) {
            $column = Column::parse($column);
        }

        $selection->columns = $columns;

        return $selection;
    }


    /**
     * @return Select
     */
    public static function getBlank() : Select
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
