<?php

namespace MissionX\DiscountsEngine;

use Closure;
use Money\Money;
use RuntimeException;

class Discount
{
    public ?string $name = null;

    public bool $canCombineWithOtherDiscounts = false;

    public bool $forceCombineWithOtherDiscounts = false;

    /**
     * Higher value = higher priority
     */
    public int $priority = 0;

    /**
     * A set of products to which the discount is limited.
     *
     * @param string[] $products
     */
    public array $products = [];

    public float $minPurchaseAmount = 0;

    public float $minQuantityAmount = 0;

    /**
     * A set of users to which the discount is limited.
     */
    public array $users = [];

    /*-----------------------------------------------------
    * Setters
    -----------------------------------------------------*/
    public function combineWithOtherDiscounts(bool $canCombineWithOtherDiscounts = true): static
    {
        $this->canCombineWithOtherDiscounts = $canCombineWithOtherDiscounts;
        return $this;
    }

    public function forceCombineWithOtherDiscounts(bool $forceCombineWithOtherDiscounts = true): static
    {
        $this->forceCombineWithOtherDiscounts = $forceCombineWithOtherDiscounts;
        return $this;
    }

    public function priority(int $priority = 0): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function appliesToProducts(array $products): static
    {
        $this->products = $products;
        return $this;
    }

    public function minPurchaseAmount(float $amount): static
    {
        $this->minPurchaseAmount = $amount;
        return $this;
    }

    public function minQuantityAmount(float $amount): static
    {
        $this->minQuantityAmount = $amount;
        return $this;
    }

    /*-----------------------------------------------------
    * Methods
    -----------------------------------------------------*/
    /**
     * @param \MissionX\DiscountsEngine\Item[]
     */
    public function assertCanBeApplied(array $items, Closure $fail)
    {
        if (
            !empty($this->products) && empty(array_intersect(
                $this->products,
                array_column($items, 'id')
            ))
        ) {
            $fail(Errors::get('limited-products'));
            return;
        }

        if ($this->minPurchaseAmount > 0 && ($total = $this->getItemsTotal($items))) {
            $fail(
                Errors::get('min-purchase-violation', [
                    '@subtotal' => number_format($total),
                    '@minPurchaseAmount' => number_format($this->minPurchaseAmount)
                ])
            );
            return;
        }

        if ($this->minQuantityAmount > 0 && ($total = $this->getItemsTotalQty($items))) {
            $fail(
                Errors::get('min-purchase-violation', [
                    '@quantity' => $total,
                    '@minQuantity' => $this->minQuantityAmount
                ])
            );
            return;
        }
    }

    /**
     * @param \MissionX\DiscountsEngine\Item[]
     */
    public function calculateDiscount(array $items): DiscountResult
    {
        throw new RuntimeException('Discount does not implement apply method.');
    }

    /**
     * @param \MissionX\DiscountsEngine\Item[]
     */
    public function apply(array $items): DiscountResult
    {
        $errorMessage = null;
        $failed = false;
        $fail = function ($message) use (&$errorMessage, &$failed) {
            $errorMessage = $message;
            $failed = true;
        };

        $this->assertCanBeApplied($items, $fail);

        if ($failed) {
            return new DiscountResult(
                name: $this->name(),
                items: $items,
                savings: 0,
                error: $errorMessage
            );
        }

        return $this->calculateDiscount($items);
    }

    /**
     * helper method to get the total items with respect to products
     *
     * @param \MissionX\DiscountsEngine\Item[]
     */
    public function getItemsTotal(array $items): float
    {
        return array_reduce($items, function (float $total, Item $item) {
            if (!empty($this->products) && !in_array($item->id, $this->products)) {
                return $total;
            }

            return $total + $item->total();
        }, 0);
    }

    /**
     * helper method to get the total items with respect to products
     *
     * @param \MissionX\DiscountsEngine\Item[]
     */
    public function getItemsTotalQty(array $items)
    {
        return array_reduce($items, function (float $total, Item $item) {
            if (!empty($this->products) && !in_array($item->id, $this->products)) {
                return $total;
            }

            return $total + $item->qty;
        }, 0);
    }

    public function name(): string
    {
        if ($this->name) {
            return $this->name;
        }

        return static::class;
    }
}
