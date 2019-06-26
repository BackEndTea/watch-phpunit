<?php

declare(strict_types=1);

use Symfony\Component\Console\Application;
use Watcher\RunCommand;

(static function () : void {
    (static function () : void {
        foreach ([
            __DIR__ . '/../vendor/autoload.php',
            __DIR__ . '/../../vendor/autoload.php',
        ] as $file) {
            if (file_exists($file)) {
                require_once $file;

                return;
            }
        }
        echo "Unable to locate autoloader file, please use composer to install your dependencies\n";
        exit(1);
    })();

    $application = new Application();
    $command     = new RunCommand();
    $application->add($command);
    $application->setDefaultCommand($command->getName());
    $application->run();
})();
