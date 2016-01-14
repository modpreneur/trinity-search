<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\Tests\Functional;

use Trinity\Bundle\SearchBundle\Tests\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application as App;
use Symfony\Component\Console\Input\StringInput;



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


}