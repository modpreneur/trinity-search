<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\Tests\Functional;

use Doctrine\ORM\EntityManager;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializerBuilder;
use Symfony\Bundle\FrameworkBundle\Console\Application as App;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\HttpFoundation\Response;
use Trinity\Bundle\SearchBundle\NQL\NQLQuery;
use Trinity\Bundle\SearchBundle\PassThroughNamingStrategy;
use Trinity\Bundle\SearchBundle\Search;
use Trinity\Bundle\SearchBundle\Tests\TestCase;
use Trinity\Bundle\SearchBundle\Utils\StringUtils;

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
     * @var bool
     */
    protected static $isInit = false;


    protected function init()
    {

        if (self::$isInit === false) {
            exec('php bin/console.php doctrine:database:drop --force');
            exec('php bin/console.php doctrine:schema:create');
            exec('php bin/console.php doctrine:schema:update');

            $kernel = static::createClient()->getKernel();
            $container = $kernel->getContainer();
            /** @var EntityManager $em */
            $em = $container->get('doctrine.orm.default_entity_manager');

            $data = new DataSet();
            $data->load($em);
        }

        self::$isInit = true;
    }

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->init();
    }


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
    protected function get($serviceName)
    {
        $kernel = static::createClient()->getKernel();
        $container = $kernel->getContainer();

        return $container->get($serviceName);
    }


    /**
     * @param string $tableName
     * @param string $queryParams
     * @return Response
     * @throws \InvalidArgumentException
     */
    protected function table($tableName, $queryParams = '')
    {
        /** @var Search $search */
        $search = $this->get('trinity.search');

        if ($tableName === 'global') {
            if (StringUtils::isEmpty($queryParams)) {
                throw new \InvalidArgumentException('Query is empty');
            }
            return $search->convertArrayToJson($search->queryGlobal($queryParams));
        }

        // Other tables
        /**  @var NQLQuery */
        $nqlQuery = $search->queryTable($tableName, $queryParams);
        return $search->convertToJson($nqlQuery, count($nqlQuery->getSelect()->getColumns()));
    }


    /**
     * @param $rows
     * @return mixed|string
     */
    protected function toJson($rows)
    {
        $json = SerializerBuilder::create()->setPropertyNamingStrategy(
            new SerializedNameAnnotationStrategy(new PassThroughNamingStrategy())
        )
        ->build()
        ->serialize($rows, 'json');

        return $json;
    }
}
