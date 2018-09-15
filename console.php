<?php
/**
 * Created by PhpStorm.
 * User: tio
 * Date: 2018-09-15
 * Time: 16:19
 */


    require __DIR__.'/vendor/autoload.php';

    use Symfony\Component\Console\Application;

    $application = new Application();

    // ... register commands
    $application->add(new \Console\Commands\GetPlanetInfoCommand());

    $application->run();