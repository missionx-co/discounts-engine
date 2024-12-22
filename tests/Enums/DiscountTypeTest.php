<?php

namespace MissionX\DiscountsEngine\Tests\Enums;

use MissionX\DiscountsEngine\Enums\DiscountType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DiscountTypeTest extends TestCase
{
    #[Test]
    #[DataProvider('discountTypeDataProvider')]
    public function it_calculate_discount($type, $amount, $expected)
    {
        $total = 100;
        $this->assertEquals($expected, $type->calculateDiscountAmount($total, $amount));
    }

    public static function discountTypeDataProvider()
    {
        return [
            'percentage' => [
                'type' => DiscountType::Percentage,
                'amount' => 20,
                'expected' => 20
            ],
            'fixed amount that is less than the provided total' => [
                'type' => DiscountType::FixedAmount,
                'amount' => 20,
                'expected' => 20
            ],
            'fixed amount that is greater than the provided total' => [
                'type' => DiscountType::FixedAmount,
                'amount' => 120,
                'expected' => 100
            ],
        ];
    }
}
