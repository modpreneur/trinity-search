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



    public function setUp()
    {
        parent::setUp();

        $kernel = $this->createClient()->getKernel();
        $container = $kernel->getContainer();
        $search = $container->get('trinity.search');
    }


    public function testParse(){
        $this->assertEquals(1, 1);
    }

}