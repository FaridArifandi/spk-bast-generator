<?php

namespace Tests\Unit;

use App\Support\Indonesia;
use PHPUnit\Framework\TestCase;

class IndonesiaTest extends TestCase
{
    public function test_terbilang(): void
    {
        $this->assertSame('seratus dua puluh tiga', strtolower(trim(Indonesia::terbilang(123))));
        $this->assertStringContainsString('juta', strtolower(Indonesia::terbilang(2500000)));
    }

    public function test_rupiah(): void
    {
        $this->assertStringContainsString('1.500.000', Indonesia::rupiah(1500000));
    }
}
