<?php

namespace Trinity\Bundle\SearchBundle;

use Symfony\Component\Routing\Router;

/**
 * Class DetailUrlProvider
 * @package Trinity\Bundle\SearchBundle
 */
class DetailUrlProvider
{
    /** @var Router */
    private $router;

    /**
     * DetailUrlProvider constructor.
     * @param $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /** @noinspection GenericObjectTypeUsageInspection */
    /**
     * @param object $entity
     * @return string
     */
    public function getUrl(/** @noinspection PhpUnusedParameterInspection */ $entity)
    {
        return null;
    }

    /**
     * @return Router
     */
    protected function getRouter()
    {
        return $this->router;
    }
}