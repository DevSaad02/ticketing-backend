<?php

declare(strict_types=1);

use Monolog\Logger;
use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;
use Monolog\Processor\UidProcessor;
use Monolog\Formatter\LineFormatter;
use Psr\Container\ContainerInterface;
use App\Services\ArrayConversionService;
use Monolog\Handler\RotatingFileHandler;
use App\Application\Settings\SettingsInterface;

return function (ContainerBuilder $containerBuilder) {
    // Configure Paris ORM
    Model::$auto_prefix_models = '\\App\\Models\\';
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

             // Use RotatingFileHandler for log rotation
            // This handler creates a new log file each day and keeps up to 30 log files
            $handler = new RotatingFileHandler($loggerSettings['path'], 30, $loggerSettings['level']);

            // Create a custom formatter with a readable date format
            $dateFormat = "d M, Y H:i:s";
            $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
            $formatter = new LineFormatter($output, $dateFormat);
            $handler->setFormatter($formatter);

            $logger->pushHandler($handler);

            return $logger;
        },
        // Define the ArrayConversionService
        ArrayConversionService::class => function (ContainerInterface $c) {
            return new ArrayConversionService();
        },
    ]);
};
