<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\NQL;

/**
 * Class WherePartType
 * @package Trinity\Bundle\SearchBundle\NQL
 */
class WherePartType
{
    const
        __default = "default",
        OPERATOR = "operator",
        CONDITION = "condition",
        SUBCONDITION = "subCondition";
}