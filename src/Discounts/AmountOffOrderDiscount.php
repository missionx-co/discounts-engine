<?php

namespace MissionX\DiscountsEngine\Discounts;

use MissionX\DiscountsEngine\Concerns\HasAmount;
use MissionX\DiscountsEngine\DataTransferObjects\DiscountResult;

class AmountOffOrderDiscount extends Discount
{
    use HasAmount;

    public function calculateDiscount(): DiscountResult
    {
        $orderTotal = $this->getPurchaseAmount();

        return new DiscountResult(
            discount: $this,
            savings: $this->type->calculateDiscountAmount($orderTotal, $this->amount),
        );
    }
}
