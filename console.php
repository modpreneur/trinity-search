<?php
require "Tests/Functional/app/AppKernel.php";


use Symfony\Bundle\FrameworkBundle\Console\Application;
use Trinity\Bundle\SearchBundle\Tests\Functional\app\AppKernel;


$kernel = new AppKernel('dev', true);
$application = new Application($kernel);
$application->run();
