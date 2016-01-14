<?php

/*
 * This file is part of the Trinity project.
 */
set_time_limit(0);

require "Tests/Functional/app/AppKernel.php";

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Debug\Debug;
use Trinity\Bundle\SearchBundle\Tests\Functional\app\AppKernel;


$input = new ArgvInput();
$env = $input->getParameterOption(array('--env', '-e'), getenv('SYMFONY_ENV') ?: 'dev');
$debug = getenv('SYMFONY_DEBUG') !== '0' && !$input->hasParameterOption(array('--no-debug', '')) && $env !== 'prod';

if ($debug) {
    Debug::enable();
}


$kernel = new AppKernel($env, $debug);
$application = new Application($kernel);
$application->run($input);

