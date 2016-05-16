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
    /** @var string $name */
    private $name;
    /** @var string $prefix */
    private $prefix;
    /** @var string $alias */
    private $alias;


    /**
     * Table constructor.
     * @param string $prefix
     * @param string $name
     * @param null | string $alias
     */
    public function __construct($prefix, $name, $alias = null)
    {
        $this->prefix = $prefix;
        $this->name = $name;

        $this->alias = null === $alias ? $this->getDefaultAlias() : $alias;
        if ($this->alias === 'group') {
            $this->alias = '_group';
        }
    }

    /**
     * @return string
     */
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