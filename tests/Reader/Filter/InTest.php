<?php

declare(strict_types=1);

namespace Yiisoft\Data\Tests\Reader\Filter;

use InvalidArgumentException;
use Yiisoft\Data\Reader\Filter\In;
use Yiisoft\Data\Reader\FilterDataValidationHelper;
use Yiisoft\Data\Tests\TestCase;

final class InTest extends TestCase
{
    public function testToArray(): void
    {
        $filter = new In('test', [1, 2]);

        $this->assertSame(['in', 'test', [1, 2]], $filter->toArray());
    }

    /**
     * @dataProvider invalidScalarValueDataProvider
     */
    public function testConstructorFailForInvalidScalarValue($value): void
    {
        $type = FilterDataValidationHelper::getValueType($value);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The value should be scalar. The $type is received.");

        new In('test', [$value]);
    }
}
