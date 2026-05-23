<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (file_exists(dirname(__DIR__).'/bootstrap/cache/config.php')) {
            throw new \RuntimeException('Run php artisan optimize:clear before tests. Cached local config can point tests at the real database.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
