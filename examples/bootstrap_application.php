<?php

declare(strict_types=1);

/**
 * Example: Bootstrapping a Laminas MVC application.
 *
 * This mirrors the typical public/index.php entry point for a Laminas MVC
 * application. It shows the minimal configuration needed to initialise and
 * run the application lifecycle.
 *
 * Note: This example is illustrative — it will not run standalone without
 * a full Laminas MVC module tree and router configuration in place.
 */

// Typically loaded via: require 'vendor/autoload.php'
// require_once __DIR__ . '/../vendor/autoload.php';

use Laminas\Mvc\Application;

/**
 * Minimal application configuration.
 *
 * In a real application this would be loaded from config/application.config.php
 * and merged with module configurations.
 *
 * @var array<string, mixed> $appConfig
 */
$appConfig = [
    'modules' => [
        'Laminas\Router',
        'Laminas\Validator',
        'Application',   // your application module
    ],
    'module_listener_options' => [
        'module_paths'      => ['./module', './vendor'],
        'config_glob_paths' => ['config/autoload/{,*.}{global,local}.php'],
    ],
];

// Application::init() builds the container, loads modules, and bootstraps listeners.
// It returns the Application instance ready to dispatch the current request.
$app = Application::init($appConfig);

// run() triggers route → dispatch → render → finish in sequence.
// The matched controller populates the response; SendResponseListener sends it.
$app->run();
