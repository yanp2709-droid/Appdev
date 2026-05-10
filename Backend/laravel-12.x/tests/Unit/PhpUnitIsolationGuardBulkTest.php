<?php

namespace Tests\Unit;

use Tests\TestCase;

class PhpUnitIsolationGuardBulkTest extends TestCase
{
    /**
     * @dataProvider isolationGuardProvider
     */
    public function test_phpunit_isolation_guard_is_stable_across_many_cases(int $caseNumber): void
    {
        $this->assertSame('testing', app()->environment(), "Case {$caseNumber}: APP_ENV must stay testing.");
        $this->assertSame('sqlite', config('database.default'), "Case {$caseNumber}: DB connection must stay sqlite.");
        $this->assertSame(':memory:', config('database.connections.sqlite.database'), "Case {$caseNumber}: DB must stay in-memory.");
        $this->assertNotSame('/var/www/database/database.sqlite', config('database.connections.sqlite.database'), "Case {$caseNumber}: must never point to live admin DB.");
        $this->assertSame('array', config('session.driver'), "Case {$caseNumber}: session driver must stay array.");
        $this->assertSame('array', config('cache.default'), "Case {$caseNumber}: cache store must stay array.");
    }

    /**
     * Provide 100 independent test cases so PHPUnit executes this guard 100 times.
     *
     * @return array<string, array{0:int}>
     */
    public static function isolationGuardProvider(): array
    {
        $cases = [];

        for ($i = 1; $i <= 100; $i++) {
            $cases["isolation_case_{$i}"] = [$i];
        }

        return $cases;
    }
}

