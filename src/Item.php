<?php

namespace MissionX\DiscountsEngine;

class Item
{
    public function __construct(
        /**
         * item identifier
         */
        public string $id,

        /**
         * item qty
         */
        public float $qty,

        /**
         * iitem unit price
         */
        public float $price,

        /**
         * amount discounted from the total price = (price * qty)
         * The total of this item will be calculated (price * qty) - $discount
         *
         * This value is useful when the amount already includes a discount or when the discount applies to each item individually. In such cases, this field will be updated accordingly
         */
        public float $discount = 0
    ) {}

    /*-----------------------------------------------------
    * Methods
    -----------------------------------------------------*/
    public function total(): float
    {
        return ($this->qty * $this->price) - $this->discount;
    }
}
