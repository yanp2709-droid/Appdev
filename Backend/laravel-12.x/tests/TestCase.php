<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'sqlite') {
            throw new RuntimeException('Unsafe test database: expected sqlite connection during tests.');
        }

        $database = (string) config('database.connections.sqlite.database');

        if ($database !== ':memory:') {
            throw new RuntimeException(
                "Unsafe test database: expected ':memory:' but got '{$database}'. "
                . 'Refusing to run tests against a persistent database.'
            );
        }
    }
}
