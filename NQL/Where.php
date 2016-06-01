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
    private static $regKeyOpValue = '/^(?<key>[^\s(<>=!]+)\s*(?<operator>(([<>!]?=|[<>]))|(LIKE))\s*(?<value>\"[^\.")]+\"|[^\s)]+)/';

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
                        $part = new WherePartType();
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

}