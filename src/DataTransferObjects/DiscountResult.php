<?php

namespace MissionX\DiscountsEngine\DataTransferObjects;

class DiscountResult
{
    public function __construct(
        /**
         * Discount name
         */
        public string $name,

        /**
         * Items after discount
         */
        public array $items,

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
