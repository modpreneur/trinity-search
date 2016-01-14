<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\NQL;

/**
 * Class Operator
 * @package Trinity\Bundle\SearchBundle\NQL
 */
class Operator
{
    const AND = 'AND';
    const OR = 'OR';
    const EQ = '=';
    const NEQ = '!=';
    const LT = '<';
    const GT = '>';
    const LTET = '<=';
    const GTET = '>=';

    private $value;


    /**
     * Operator constructor.
     * @param null $operator
     */
    public function __construct($operator = null)
    {
        if ($operator == null) {
            $this->value = self::EQ;
        } else {
            $this->value = $operator;
        }
    }


    /**
     * @param string|Operator $operator
     * @return bool
     */
    public function compareTo($operator) : bool
    {
        if ($operator instanceof Operator) {
            return $operator->value === $this->value;
        } else {
            return $operator === $this->value;
        }
    }
}