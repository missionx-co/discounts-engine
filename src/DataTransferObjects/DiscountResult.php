<?php

namespace MissionX\DiscountsEngine\DataTransferObjects;

use MissionX\DiscountsEngine\Discounts\Discount;

class DiscountResult
{
    public function __construct(
        public Discount $discount,

        /**
         * amount saved by the discount
         */
        public float $savings = 0,

        public ?string $error = null,
    ) {}

    public function wasApplied(): bool
    {
        return is_null($this->error);
    }
}
