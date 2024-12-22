<?php

namespace MissionX\DiscountsEngine;

use MissionX\DiscountsEngine\Discounts\Discount;

class DiscountsEngine
{
    public array $items = [];

    public array $originalItems = [];

    /**
     * The Discounts that will be applied to items
     *
     * @var \MissionX\DiscountsEngine\Discount[] $discounts
     */
    public array $discounts = [];

    /**
     * Discounts that were applied to items
     *
     * @var \MissionX\DiscountsEngine\Discount[] $discounts
     */
    public array $appliedDiscounts = [];

    public function addDiscount(Discount $discount): static
    {
        $this->discounts[] = $discount;
        return $this;
    }

    public function process(array $items)
    {
        $this->originalItems = $items;
        foreach ($items as $item) {
            $this->items[] = clone $item;
        }
    }
}
