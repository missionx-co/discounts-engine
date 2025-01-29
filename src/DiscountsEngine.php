<?php

namespace MissionX\DiscountsEngine;

use MissionX\DiscountsEngine\DataTransferObjects\DiscountResult;
use MissionX\DiscountsEngine\DataTransferObjects\Item;
use MissionX\DiscountsEngine\Discounts\Discount;

class DiscountsEngine
{
    public array $items = [];

    public array $originalItems = [];

    /**
     * The Discounts that will be applied to items
     *
     * @var \MissionX\DiscountsEngine\Discount[]
     */
    public array $discounts = [];

    /**
     * Discounts that were applied to items
     *
     * @var \MissionX\DiscountsEngine\Discount[]
     */
    public array $processedDiscounts = [];

    public function addDiscount(Discount $discount): static
    {
        $this->discounts[] = $discount;

        return $this;
    }

    /**
     * @param  Item[]  $items
     */
    public function process(array $items): static
    {
        $this->setItems($items);
        $discounts = $this->determineDiscountsThatShouldBeApplied();
        $this->processedDiscounts = [];

        foreach ($discounts as $discount) {
            $result = $discount->applyTo($this->items)->calculate();
            $this->processedDiscounts[] = $result;

            // we need each discount to have it's information for the savings that that was done
            $this->items = $this->clone($result->discount->getItems());
        }

        return $this;
    }

    public function total(): float
    {
        return $this->totalBeforeDiscount() - $this->savings();
    }

    public function savings(): float
    {
        return array_reduce($this->processedDiscounts, fn(float $total, DiscountResult $result) => $total + $result->savings, 0);
    }

    public function totalBeforeDiscount(): float
    {
        return array_reduce($this->originalItems, fn(float $total, Item $item) => $total + $item->total(), 0);
    }

    public function details()
    {
        return $this->processedDiscounts;
    }

    /**
     * @param  Item[]  $items
     */
    public function setItems(array $items): static
    {
        $this->originalItems = $items;
        $this->items = $this->clone($items);

        return $this;
    }

    public function hasAnyProcessedDiscountsApplied(): bool
    {
        foreach ($this->processedDiscounts as $discount) {
            if ($discount->wasApplied()) {
                return true;
            }
        }

        return false;
    }

    public function hasAllProcessedDiscountsApplied(): bool
    {
        foreach ($this->processedDiscounts as $discount) {
            if (!$discount->wasApplied()) {
                return false;
            }
        }

        return true;
    }

    protected function sortDiscountsByPriority()
    {
        usort($this->discounts, function (Discount $a, Discount $b) {
            if ($a->priority == $b->priority) {
                return 0;
            }

            return $a->priority->value > $b->priority->value ? -1 : 1;
        });
    }

    protected function determineDiscountsThatShouldBeApplied(): array
    {
        if (count($this->discounts) <= 1) {
            return $this->discounts;
        }

        $this->sortDiscountsByPriority();
        /** @var Discount */
        $highestPriorityDiscount = $this->discounts[0];
        if (! $highestPriorityDiscount->canCombineWithOtherDiscounts && ! $highestPriorityDiscount->forceCombineWithOtherDiscounts) {
            // get the highest priority discount and other discounts that we're forced to combine with the others
            return array_filter($this->discounts, fn(Discount $discount) => $discount == $highestPriorityDiscount || $discount->forceCombineWithOtherDiscounts);
        }

        // get all discounts that can be combined and the we're foced to combine
        return array_filter($this->discounts, fn(Discount $discount) => $discount->canCombineWithOtherDiscounts || $discount->forceCombineWithOtherDiscounts);
    }

    protected function clone(array $items): array
    {
        $clone = [];
        foreach ($items as $key => $item) {
            $clone[$key] = clone $item;
        }

        return $clone;
    }
}
