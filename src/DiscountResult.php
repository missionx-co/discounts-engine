<?php

namespace MissionX\DiscountsEngine;

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
}
