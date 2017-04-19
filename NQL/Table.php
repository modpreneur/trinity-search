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
    public function __construct(string $prefix, string $name, ?string $alias = null)
    {
        $this->prefix = $prefix;
        $this->name = $name;

        $this->alias = $alias ?? $this->getDefaultAlias();

        // Special cases
        if ($this->alias === 'group') {
            $this->alias = '_group';
        } elseif ($this->alias === 'order') {
            $this->alias = '_order';
        }
    }

    /**
     * @return string
     */
    private function getDefaultAlias(): string
    {
        return strtolower($this->name);
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }


    /**
     * @return string|null
     */
    public function getAlias(): string
    {
        return $this->alias;
    }
}
