<?php

declare(strict_types=1);

namespace Rector\CodeQuality\Tests\Rector\Identical\GetClassToInstanceOfRector;

use Iterator;
use Rector\CodeQuality\Rector\Identical\GetClassToInstanceOfRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class GetClassToInstanceOfRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideDataForTest()
     */
    public function test(string $file): void
    {
        $this->doTestFile($file);
    }

    public function provideDataForTest(): Iterator
    {
        yield [__DIR__ . '/Fixture/fixture.php.inc'];
    }

    protected function getRectorClass(): string
    {
        return GetClassToInstanceOfRector::class;
    }
}
