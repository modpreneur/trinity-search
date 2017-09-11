<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\Tests\Functional;

use Trinity\Bundle\SearchBundle\Tests\Functional\Entity\Product;

/**
 * Class ProductTest
 * @package Trinity\Bundle\SearchBundle\Tests\Functional
 */
class ProductTest extends WebTestCase
{

    /*
     *
     * (name)
     * (id, name)
     *
     * {id > 8}
     * {id>1 AND id<3}
     * {id != 1}
     * {id = 1 OR id = 2}
     * LIMIT=2
     * LIMIT=2 OFFSET=1
     * ORDER BY id DESC
     *
     */


    public function getAllProducts()
    {
        $repository = $this
            ->get('doctrine.orm.default_entity_manager')
            ->getRepository('Search:Product');

        $products = $repository
            ->findAll();

        return $products;
    }


    /**
     * select all
     */
    public function testAllProduct()
    {
        $products = $this->getAllProducts();
        $rows = [];

        /**
         * @var Product[] $products
         */
        foreach ($products as $product) {
            $rows[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'shipping' => [
                    'id' => $product->getShipping()->getId(),
                    'price' => $product->getShipping()->getPrice(),
                ],
            ];
        }

        $this->assertEquals(
            $this->toJson($rows),
            $this->table('product')
        );
    }


    /**
     * select name
     */
    public function testAllProduct_name()
    {
        $products = $this->getAllProducts();
        $rows = [];

        /**
         * @var Product[] $products
         */
        foreach ($products as $product) {
            $rows[] = [
                'name' => $product->getName(),
            ];
        }

        $this->assertEquals(
            $this->toJson($rows),
            $this->table('product', '(name)')
        );
    }


    /**
     * select id, name
     */
    public function testAllProduct_id_name()
    {
        $products = $this->getAllProducts();
        $rows = [];

        /**
         * @var Product[] $products
         */
        foreach ($products as $product) {
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


    /**
     * id = 10
     */
    public function testOneProduct()
    {
        $products = $this->getAllProducts();

        $p = [];
        $p[] = $products[9];

        $this->assertEquals(
            $this->toJson($p),
            $this->table('product', '{id=10}')
        );
    }


    /**
     * id > 9
     */
    public function testMoreThen()
    {
        $products = $this->getAllProducts();

        $p = [];

        $p[] = $products[9];
        $p[] = $products[10];


        $this->assertEquals(
            $this->toJson($p),
            $this->table('product', '{id>9}')
        );
    }


    /**
     * id > 9
     */
    public function testMoreThenAndLessThen()
    {
        $products = $this->getAllProducts();
        $p = [];

        $p[] = $products[1];

        $this->assertEquals(
            $this->toJson($p),
            $this->table('product', '{id>1 AND id<3}')
        );

        $this->assertEquals(
            $this->toJson($p),
            $this->table('product', '{ (id>1)  AND id < 3}')
        );
    }


    /**
     * id != 1
     */
    public function testUnset()
    {
        $products = $this->getAllProducts();

        $p = [];

        for ($i = 1; $i < count($products); $i++) {
            $p[] = $products[$i];
        }

        $this->assertEquals(
            $this->toJson($p),
            $this->table('product', '{id != 1}')
        );
    }


    /**
     * id = 1 OR id = 2
     */
    public function testOR()
    {
        $products = $this->getAllProducts();

        $p = [];

        $p[] = $products[0];
        $p[] = $products[1];

        $this->assertEquals(
            $this->toJson($p),
            $this->table('product', '{id = 1 OR id = 2}')
        );
    }


    /**
     * LIMIT=2
     */
    public function testLimit(){

        $products = $this->getAllProducts();

        $p = [];

        $p[] = $products[0];
        $p[] = $products[1];

        $this->assertEquals(
            $this->toJson($p),
            $this->table('product', 'LIMIT=2')
        );
    }


    /**
     * offset=2
     */
    public function testOffset(){

        $products = $this->getAllProducts();

        $p = [];

        $p[] = $products[1];
        $p[] = $products[2];

        $this->assertEquals(
            $this->toJson($p),
            $this->table('product', 'LIMIT=2 OFFSET=1')
        );
    }


    /**
     * order desc
     */
    public function testOrderDESC(){

        $products = $this->getAllProducts();

        $p = [];

        $p[] = $products[10];
        $p[] = $products[9];
        $p[] = $products[8];
        $p[] = $products[7];
        $p[] = $products[6];
        $p[] = $products[5];
        $p[] = $products[4];
        $p[] = $products[3];
        $p[] = $products[2];
        $p[] = $products[1];
        $p[] = $products[0];

        $this->assertEquals(
            $this->toJson($p),
            $this->table('product', 'ORDERBY id DESC')
        );
    }

    public function testProductContainingQuotesInName(){
        $repository = $this
            ->get('doctrine.orm.default_entity_manager')
            ->getRepository('Search:Product');

        $products = $repository
            ->findBy(['name' => 'Sample product with "quoted" word in name']);

        $this->assertEquals(
            $this->toJson($products),
            $this->table('product', '{name="Sample product with \"quoted\" word in name"}')
        );
    }

}