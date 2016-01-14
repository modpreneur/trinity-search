<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\Tests\Functional;


/**
 * Class ToDoTest
 * @package Trinity\Bundle\GridBundle\Tests\Functional
 */
class QueryTest extends WebTestCase
{

    /**
     * @var bool
     */
    protected $isInit = false;


    protected function init(){

        if($this->isInit === false){

            //dump(exec('php console.php doctrine:database:drop --force'));
            //dump(exec('php console.php doctrine:database:create'));

            $kernel    = $this->createClient()->getKernel();
            $container = $kernel->getContainer();
            $em = $container->get('doctrine.orm.default_entity_manager');

            $data = new DataSet();
            $data->load($em);
        }

        $this->isInit = true;
    }


    public function setUp()
    {
        parent::setUp();

        $kernel = $this->createClient()->getKernel();
        $container = $kernel->getContainer();
        $search = $container->get('trinity.search');


        $this->init();
    }


    // -----


    /**
     *
     */
    public function testParse(){

        $kernel = $this->createClient()->getKernel();
        $container = $kernel->getContainer();
        $search = $container->get('trinity.search');


    }

}