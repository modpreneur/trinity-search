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


    public function getAllProducts(){
        $repository = $this
            ->get('doctrine.orm.default_entity_manager')
            ->getRepository('Search:Product');
        $products = $repository->findAll();

        return $products;
    }


    /**
     *
     */
    public function testAllProduct(){
        $products = $this->getAllProducts();
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

        $this->assertEquals(
            $this->toJson($rows),
            $this->table('product')
        );
    }


    public function testAllProduct_name(){
        $products = $this->getAllProducts();
        $rows = [];

        /**
         * @var Product[] $products
         */
        foreach($products as $product){
            $rows[] = [
                'name' => $product->getName(),
            ];
        }

        $this->assertEquals(
            $this->toJson($rows),
            $this->table('product', '(name)')
        );
    }


    public function testAllProduct_id_name(){
        $products = $this->getAllProducts();
        $rows = [];

        /**
         * @var Product[] $products
         */
        foreach($products as $product){
            $rows[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
            ];
        }

        $this->assertEquals(
            $this->toJson($rows),
            $this->table('product', '(id, name)')
        );
    }

}