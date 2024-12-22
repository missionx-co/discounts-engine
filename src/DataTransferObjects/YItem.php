<?php

namespace MissionX\DiscountsEngine\DataTransferObjects;

class YItem
{
    public function __construct(
        public string $itemId,

        /**
         * Qty of the item to apply discount to
         */
        public int $qty = 1
    ) {}
}
