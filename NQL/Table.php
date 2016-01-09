<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\NQL;


/**
 * Class Table
 * @package Trinity\Bundle\SearchBundle\NQL
 */
class Table
{
    private $name;
    private $prefix;
    private $alias;


    /**
     * Table constructor.
     * @param $prefix
     * @param $name
     * @param null $alias
     */
    function __construct($prefix, $name, $alias = null)
    {
        $this->prefix = $prefix;
        $this->name = $name;

        $this->alias = is_null($alias) ? $this->getDefaultAlias() : $alias;
    }


    private function getDefaultAlias()
    {
        return strtolower($this->name);
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }


    /**
     * @return string|null
     */
    public function getAlias()
    {
        return $this->alias;
    }

}