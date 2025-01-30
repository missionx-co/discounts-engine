<?php

namespace MissionX\DiscountsEngine\Discounts;

use MissionX\DiscountsEngine\Concerns\HasAmount;
use MissionX\DiscountsEngine\DataTransferObjects\DiscountResult;
use MissionX\DiscountsEngine\DataTransferObjects\Item;

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
         * @var callable(array $items): array
         */
        protected $affectedItemsSelector = null
    ) {
        parent::__construct($name);

        if (! $this->affectedItemsSelector) {
            $this->affectedItemsSelector = fn(array $items) => $items;
        }
    }

    /**
     * @param  callable(array $items): array  $affectedItems  only applicable items will be checked with this selector
     */
    public function selectAffectedItemsUsing(callable $affectedItems): static
    {
        $this->affectedItemsSelector = $affectedItems;

        return $this;
    }

    public function calculateDiscount(): DiscountResult
    {
        $applyToItems = isset($this->affectedItemsSelector)
            ? call_user_func($this->affectedItemsSelector, $this->applicableItems)
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
            discount: $this,
            savings: $totalSavings,
        );
    }
}
