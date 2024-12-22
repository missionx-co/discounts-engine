<?php

namespace MissionX\DiscountsEngine\DataTransferObjects;

class Item
{
    public function __construct(
        /**
         * item identifier
         */
        public string $id,

        /**
         * item identifier
         */
        public string $name,

        /**
         * item qty
         */
        public int $qty,

        /**
         * iitem unit price
         */
        public float $price,

        /**
         * Item type
         */
        public string $type = 'unkown',

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
