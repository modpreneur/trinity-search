<?php

namespace Trinity\SearchBundle\NQL;


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