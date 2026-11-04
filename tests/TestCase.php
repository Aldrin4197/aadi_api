<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use PDO;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerSqliteMathFunctions();
    }

    /** Some SQLite builds (e.g. Windows PHP) ship without the math functions used by distance queries. */
    private function registerSqliteMathFunctions(): void
    {
        $pdo = DB::connection()->getPdo();
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            return;
        }

        foreach (['acos', 'cos', 'sin', 'radians' => 'deg2rad'] as $name => $callback) {
            $pdo->sqliteCreateFunction(is_int($name) ? $callback : $name, $callback, 1);
        }
    }
}
