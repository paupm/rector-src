<?php

declare(strict_types=1);

namespace Rector\Doctrine\Tests\Rector\Property\RemoveTemporaryUuidColumnPropertyRector;

use Iterator;
use Rector\Doctrine\Rector\Property\RemoveTemporaryUuidColumnPropertyRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class RemoveTemporaryUuidColumnPropertyRectorTest extends AbstractRectorTestCase
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
        return RemoveTemporaryUuidColumnPropertyRector::class;
    }
}
