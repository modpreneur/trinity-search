<?php

/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Trinity\Bundle\SearchBundle\Tests\Functional\app\AppKernel;

/**
 * Class TestCase
 * @package Trinity\Bundle\SearchBundle\Tests
 */
class TestCase extends WebTestCase
{
    protected static function getKernelClass()
    {
        return AppKernel::class;
    }
}
