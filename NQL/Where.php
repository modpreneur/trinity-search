<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\NQL;

use Trinity\Bundle\SearchBundle\Exception\SyntaxErrorException;
use Trinity\Bundle\SearchBundle\Utils\StringUtils;

/**
 * Class Where
 * @package Trinity\Bundle\SearchBundle\NQL
 */
class Where
{
    /**
     * Regular expression to get key=value from condition
     * @var string
     */
    private static $regKeyOpValue = '/^(?<key>[^\s<>=!]+)\s*(?<operator>(([<>!]?=|[<>]))|(LIKE))\s*(?<value>\"[^\.")]+\"|[^\s)]+)/';

    /**
     * @var WherePart[]
     */
    private $conditions = [];


    /**
     * Where constructor.
     */
    private function __construct()
    {
    }


    /**
     * Parse WHERE string
     *
     * @param $str
     * @return Where
     * @throws \Trinity\Bundle\SearchBundle\Exception\SyntaxErrorException
     */
    public static function parse($str = '') : Where
    {
        $condition = new Where();
        $condition->setConditions(self::parseCondition($str));

        return $condition;
    }


    /**
     * @param WherePart[] $conditions
     */
    private function setConditions($conditions)
    {
        $this->conditions = $conditions;
    }


    /**
     * Parse condition;
     *
     * @param $str
     * @return array
     * @throws SyntaxErrorException
     */
    private static function parseCondition($str) : array
    {
        $parts = [];

        // REMOVE TRAILING SPACES
        $str = trim($str);

        // LOOP THROUGH ALL CHARACTERS
        $iMax = strlen($str);

        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < $iMax; $i++) {
            // IF CHARACTER IS LEFT BRACKET, FIND PAIR BRACKET AND RECURSIVELY FIND CONDITIONS WITHIN THESE BRACKETS
            if ($str[$i] === '(') {
                $pairBracketIndex = self::findPairBracketIndex($str, $i + 1);

                if ($pairBracketIndex > 0) {
                    $part = new WherePart();
                    $part->type = WherePartType::SUBCONDITION;

                    $subCondition = substr($str, $i + 1, $pairBracketIndex - $i - 1);
                    $part->baseExpr = trim($subCondition);
                    $part->subTree = self::parseCondition($subCondition);

                    $parts[] = $part;

                    $i = $pairBracketIndex;
                    continue;
                } else {
                    throw new SyntaxErrorException('Missing pair bracket');
                }

            } // IF THERE IS SPACE - IT IS SIGN THAT THERE WILL BE "AND" OR "OR" CONDITION
            else {
                if ($str[$i] === ' ') {
                    if (trim(substr($str, $i, 4)) === Operator:: AND) {
                        $part = new WherePart();
                        $part->type = WherePartType::OPERATOR;
                        $part->value = Operator:: AND;

                        $parts[] = $part;
                        $i += 3;
                    } else {
                        if (trim(substr($str, $i, 3)) === Operator:: OR) {
                            $part = new WherePart();
                            $part->type = WherePartType::OPERATOR;
                            $part->value = Operator:: OR;

                            $parts[] = $part;
                            $i += 2;
                        }
                    }
                } // OTHERWISE WE EXPECTING KEY=VALUE
                else {
                    $match = [];
                    $wasFound = preg_match(self::$regKeyOpValue, substr($str, $i), $match);

                    if ($wasFound) {
                        $part = new WherePart();
                        $part->type = WherePartType::CONDITION;
                        $part->key = Column::parse($match['key']);
                        $value = $match['value'];
                        if (StringUtils::startsWith($value, '"') && StringUtils::endsWith($value, '"')) {
                            $value = StringUtils::substring($value, 1, StringUtils::length($value) - 1);
                        }
                        $part->value = $value;
                        $part->operator = $match['operator'];

                        $parts[] = $part;
                        $i = $i + strlen($match[0]) - 1;
                    } else {
                        $part = new WherePart();
                        $part->type = 'UNKNOWN';
                        $part->value = $str[$i];

                        $parts[] = $part;

                        $context = self::getErrorContext($str, $i);

                        throw new SyntaxErrorException(
                            "Unrecognized char sequence at '{$context['subString']}' starting from index {$context['errorAt']}"
                        );
                    }

                }
            }
        }

        return $parts;
    }


    /**
     * Finds index of pair bracket. Returns  positive number (index) if bracket found, otherwise returns -1
     *
     * @param string $str
     * @param int $start
     * @return int
     */
    private static function findPairBracketIndex($str, $start = 1) : int
    {
        $level = 1;

        $iMax = strlen($str);
        for ($i = $start; $i < $iMax; $i++) {
            if ($str[$i] === '(') {
                $level++;
            } else {
                if ($str[$i] === ')') {
                    $level--;
                    if ($level === 0) {
                        return $i;
                    }
                }
            }
        }

        return -1;
    }


    /**
     * @param string $str
     * @param int $index
     * @return array
     */
    private static function getErrorContext($str, $index) : array
    {
        $length = 16;
        $start = $index >= $length / 2 ? $index - $length / 2 : 0;

        return ['subString' => substr($str, $start, $length), 'errorAt' => $index - $start];
    }


    /**
     * @return Where
     */
    public static function getBlank() : Where
    {
        return new Where();
    }


    /**
     * Return conditions
     *
     * @return WherePart[]
     */
    public function getConditions() : array
    {
        return $this->conditions;
    }


    /** @noinspection MoreThanThreeArgumentsInspection
     * @param WherePart[] $whereParts
     * @param $oldColumnName
     * @param array $newColumnNames
     * @param string $joiningOperator
     */
    public function replaceColumn(
        $oldColumnName,
        array $newColumnNames,
        $joiningOperator = Operator:: OR,
        /** @noinspection ParameterByRefWithDefaultInspection */
        &$whereParts = null
    ) {
        if ($whereParts === null) {
            $whereParts = &$this->conditions;
        }

        $newColumnNamesCount = count($newColumnNames);

        if (count($newColumnNames)) {
            $partsCount = count($whereParts);

            // Loop through all where parts
            /** @noinspection ForeachInvariantsInspection */
            for ($i = 0; $i < $partsCount; $i++) {

                $wherePart = $whereParts[$i];

                // Match column we are looking for
                if ($wherePart->type === WherePartType::CONDITION && $wherePart->key->getName() === $oldColumnName) {
                    $column = $wherePart->key;

                    if ($newColumnNamesCount > 1) { // If column is being replaced by multiple ones

                        // Create subcondition instead of original condition
                        $newWherePart = new WherePart();
                        $newWherePart->type = WherePartType::SUBCONDITION;
                        $newWherePart->subTree = [];

                        foreach ($newColumnNames as $j => $newColumnName) {
                            // Create and add condition into subcondition
                            $newInnerWherePart = new WherePart();
                            $newInnerWherePart->type = WherePartType::CONDITION;
                            $newInnerWherePart->key = new Column(
                                $newColumnName,
                                $column->getAlias(),
                                $column->getWrappingFunction(),
                                $column->getJoinWith()
                            );

                            $newInnerWherePart->value = $wherePart->value;
                            $newInnerWherePart->operator = $wherePart->operator;

                            $newWherePart->subTree[] = $newInnerWherePart;

                            // Add joining operator
                            if ($j < $newColumnNamesCount - 1) {
                                $operatorWherePart = new WherePart();
                                $operatorWherePart->type = WherePartType::OPERATOR;
                                $operatorWherePart->value = $joiningOperator;

                                $newWherePart->subTree[] = $operatorWherePart;
                            }
                        }

                        $whereParts[$i] = $newWherePart;

                        $partsCount = count($whereParts);

                    } else { // If column is replaced to another single one
                        $column->setName($newColumnNames[0]);
                    }

                } else if ($wherePart->type === WherePartType::SUBCONDITION) {
                    $this->replaceColumn($oldColumnName, $newColumnNames, $joiningOperator, $wherePart->subTree);
                }
            }
        }
    }

    /**
     * @param $columnName
     * @param $modifier
     * @param null $whereParts
     */
    public function modifyCondition(
        $columnName,
        $modifier,
        /** @noinspection ParameterByRefWithDefaultInspection */
        &$whereParts = null
    ) {
        if ($modifier === null || !is_callable($modifier)) {
            return;
        }

        if ($whereParts === null) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $whereParts = &$this->conditions;
        }

        $partsCount = count($whereParts);

        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < $partsCount; $i++) {
            $wherePart = $whereParts[$i];

            if ($wherePart->type === WherePartType::CONDITION && $wherePart->key->getName() === $columnName) {
                $modifier($wherePart);

            } else if ($wherePart->type === WherePartType::SUBCONDITION) {
                $this->modifyCondition($columnName, $modifier, $wherePart->subTree);
            }
        }


    }
}
