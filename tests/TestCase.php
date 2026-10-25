<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get('database.connections.'.$connection.'.database');
        if (! is_string($database) || ! str_ends_with($database, '_testing')) {
            throw new RuntimeException('Tests require a dedicated database ending in _testing.');
        }

        return $app;
    }
}
