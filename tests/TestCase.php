<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! app()->environment('testing')) {
            throw new RuntimeException('Tests must run with APP_ENV=testing.');
        }

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($connection === 'mysql' && ! str_contains(strtolower($database), 'test')) {
            throw new RuntimeException('Refusing to run tests on a non-test MySQL database.');
        }
    }
}
