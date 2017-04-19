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
    const LIKE = 'LIKE';

    private $value;


    /**
     * Operator constructor.
     * @param string $operator
     */
    public function __construct(?string $operator = self::EQ)
    {
        $this->value = $operator;
        if ($operator === null) {
            $this->value = self::EQ;
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
        }
        return $operator === $this->value;
    }
}
