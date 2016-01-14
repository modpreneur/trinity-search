<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\Tests\Functional;

use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializerBuilder;
use Trinity\Bundle\SearchBundle\PassThroughNamingStrategy;
use Trinity\Bundle\SearchBundle\Tests\Functional\Entity\Product;


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

            exec('php console.php doctrine:database:drop --force');
            exec('php console.php doctrine:schema:create');
            exec('php console.php doctrine:schema:update');

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
        $this->init();
    }


    /**
     *
     */
    public function testAllProduct(){
        $repository = $this
            ->get('doctrine.orm.default_entity_manager')
            ->getRepository('Search:Product');
        $products = $repository->findAll();
        $rows = [];

        /**
         * @var Product[] $products
         */
        foreach($products as $product){
            $rows[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'shipping' => [
                    'id' => $product->getShipping()->getId(),
                    'price' => $product->getShipping()->getPrice()
                ]
            ];
        }

        $json = SerializerBuilder::create()->setPropertyNamingStrategy(
            new SerializedNameAnnotationStrategy(new PassThroughNamingStrategy())
        )->build()->serialize($rows, 'json');

        $this->assertEquals($json, $this->table('product'));
    }


    public function testAllProduct_name(){
        $repository = $this
            ->get('doctrine.orm.default_entity_manager')
            ->getRepository('Search:Product');
        $products = $repository->findAll();
        $rows = [];

        /**
         * @var Product[] $products
         */
        foreach($products as $product){
            $rows[] = [
                'name' => $product->getName(),
            ];
        }

        $json = SerializerBuilder::create()->setPropertyNamingStrategy(
            new SerializedNameAnnotationStrategy(new PassThroughNamingStrategy())
        )->build()->serialize($rows, 'json');

        $this->assertEquals($json, $this->table('product', '(name)'));
    }

}