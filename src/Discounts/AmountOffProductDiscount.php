<?php

namespace MissionX\DiscountsEngine\Discounts;

use MissionX\DiscountsEngine\Concerns\HasAmount;
use MissionX\DiscountsEngine\DataTransferObjects\Item;
use MissionX\DiscountsEngine\DataTransferObjects\DiscountResult;


class AmountOffProductDiscount extends Discount
{
    use HasAmount;

    public function __construct(
        public ?string $name = null,

        /**
         * Selector to select the items that this coupon should affect
         *
         * Useful when you want to apply the discount to the cheapest applicable product only
         *
         * only applicable items will be checked with this selector
         *
         * @var callable(Item $items): bool
         */
        protected $affectedItemsSelector = null
    ) {
        if (!$this->affectedItemsSelector) {
            $this->affectedItemsSelector = fn(Item $item) => true;
        }
    }

    /**
     * @param callable(Item $items): bool $affectedItems only applicable items will be checked with this selector
     */
    public function selectAffectedItemsUsing(callable $affectedItems): static
    {
        $this->affectedItemsSelector = $affectedItems;
        return $this;
    }

    public function calculateDiscount(): DiscountResult
    {
        $applyToItems = isset($this->affectedItemsSelector)
            ? array_filter($this->applicableItems, $this->affectedItemsSelector)
            : $this->applicableItems;

        $totalSavings = 0;
        foreach ($applyToItems as $item) {
            $unitPriceWithDiscount = $item->total() / $item->qty;
            $savingsPerItem = $this->type->calculateDiscountAmount($unitPriceWithDiscount, $this->amount);
            $savings = $savingsPerItem * $item->qty;
            $item->discount += $savings;
            $totalSavings += $savings;
        }

        return new DiscountResult(
            name: $this->name(),
            items: $this->applicableItems,
            savings: $totalSavings,
        );
    }
}
