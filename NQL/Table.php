<?php

namespace Trinity\SearchBundle\NQL;


class Table
{
    private $name;
    private $prefix;
    private $alias;

    function __construct($prefix, $name, $alias = null)
    {
        $this->prefix = $prefix;
        $this->name = $name;

        $this->alias = is_null($alias) ? $this->getDefaultAlias() : $alias;
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

    private function getDefaultAlias() {
        return strtolower($this->name);
    }

}