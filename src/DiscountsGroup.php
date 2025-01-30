<?php

namespace MissionX\DiscountsEngine;

use MissionX\DiscountsEngine\Discounts\Discount;
use MissionX\DiscountsEngine\DataTransferObjects\DiscountResult;

class DiscountsGroup
{
    protected array $results = [];

    protected string $id;

    public function __construct(
        public array $discounts
    ) {
        $this->sortDiscounts();
        $this->id = $this->generateId();
    }

    public function process(array $items): float
    {
        // make sure we're not manipulating original items
        $items = $this->clone($items);

        foreach ($this->discounts as $discount) {
            $result = $discount->applyTo($items)->calculate();
            $this->results[] = $result;

            // we need each discount to have it's information for the savings that that was done
            $items = $this->clone($discount->getItems());
        }

        return $this->savings();
    }

    public function savings(): float
    {
        return array_reduce($this->results, fn(float $total, DiscountResult $result) => $total + $result->savings, 0);
    }

    public function details()
    {
        return $this->results;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function hasAnyProcessedDiscountsApplied(): bool
    {
        foreach ($this->results as $discount) {
            if ($discount->wasApplied()) {
                return true;
            }
        }

        return false;
    }

    public function hasAllProcessedDiscountsApplied(): bool
    {
        foreach ($this->results as $discount) {
            if (!$discount->wasApplied()) {
                return false;
            }
        }

        return true;
    }

    protected function clone(array $items): array
    {
        $clone = [];
        foreach ($items as $key => $item) {
            $clone[$key] = clone $item;
        }

        return $clone;
    }

    protected function generateId(): string
    {
        $ids = array_map(fn(Discount $discount) => $discount->id(), $this->discounts);
        return implode('', $ids);
    }

    // discounts
    protected function sortDiscounts()
    {
        // sort discounts by priority first, sort desc
        usort($this->discounts, function (Discount $a, Discount $b) {
            if ($a->priority == $b->priority) {
                return 0;
            }

            return $a->priority->value > $b->priority->value ? -1 : 1;
        });

        $automaticDiscounts = array_filter($this->discounts, fn(Discount $discount) => $discount->isAutomatic);
        $nonAutomaticDiscounts = array_filter($this->discounts, fn(Discount $discount) => !$discount->isAutomatic);

        $this->discounts = [
            ...$automaticDiscounts,
            ...$nonAutomaticDiscounts
        ];
    }
}
