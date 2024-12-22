<?php

namespace MissionX\DiscountsEngine\Enums;

enum DiscountType: string
{
    case Percentage = 'percentage';
    case FixedAmount = 'fixed-amount';

    public function calculateDiscountAmount(float $total, float $amount): float
    {
        if ($this == self::FixedAmount) {
            return min($total, $amount);
        }

        return round($total * $amount / 100, 2);
    }
}
