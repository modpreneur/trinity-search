<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\NQL;

/**
 * Class WherePart
 * @package Trinity\Bundle\SearchBundle\NQL
 */
class WherePart
{
    public $type;
    public $baseExpr;
    public $operator;

    /** @var  Column */
    public $key;
    public $value;
    public $subTree;
}
