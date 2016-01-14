<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\Tests\Functional;

use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializerBuilder;
use Symfony\Bundle\FrameworkBundle\Console\Application as App;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\HttpFoundation\Response;
use Trinity\Bundle\SearchBundle\PassThroughNamingStrategy;
use Trinity\Bundle\SearchBundle\Tests\TestCase;


/**
 * Class WebTestCase
 * @package Trinity\Bundle\SearchBundle\Tests\Functional
 */
class WebTestCase extends TestCase
{


    /**
     * @var App
     */
    protected static $application;


    /**
     * @param $command
     * @return int
     * @throws \Exception
     */
    protected static function runCommand($command)
    {
        $command = sprintf('%s --quiet', $command);

        return self::getApplication()->run(new StringInput($command));
    }


    /**
     * @return App
     */
    protected static function getApplication()
    {
        if (null === self::$application) {
            $client = static::createClient();

            self::$application = new App($client->getKernel());
            self::$application->setAutoExit(false);
        }

        return self::$application;
    }


    /**
     * @param string $serviceName
     * @return object
     */
    protected function get($serviceName){
        $kernel = $this->createClient()->getKernel();
        $container = $kernel->getContainer();

        return $container->get($serviceName);
    }


    /**
     * @param string $tableName
     * @param $queryParams
     * @return Response
     */
    protected function table($tableName, $queryParams = ""){

        $search = $this->get('trinity.search');

        if ($tableName === "global") {
            return $search->queryGlobal($queryParams);
        } else {
            $nqlQuery = $search->queryTable($tableName, $queryParams);

            $skipSelection = count($nqlQuery->getSelect()->getColumns());

            $entities = $nqlQuery->getQueryBuilder($skipSelection)->getQuery()->getResult();

            if (!$skipSelection) {
                return SerializerBuilder::create()->setPropertyNamingStrategy(
                    new SerializedNameAnnotationStrategy(new PassThroughNamingStrategy())
                )->build()->serialize($entities, 'json');

            }

            $result = [];

            $select = $nqlQuery->getSelect();

            foreach ($entities as $entity) {
                $result[] = $this->select($search, $select->getColumns(), $entity);
            }

            return
                SerializerBuilder::create()->setPropertyNamingStrategy(
                    new SerializedNameAnnotationStrategy(new PassThroughNamingStrategy())
                )->build()->serialize($result, 'json');

        }
    }


}