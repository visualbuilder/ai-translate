<?php

namespace Visualbuilder\AiTranslate\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Visualbuilder\AiTranslate\AiTranslateServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app) {
        return [
            AiTranslateServiceProvider::class,
        ];
    }
}
