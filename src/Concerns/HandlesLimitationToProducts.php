<?php

namespace MissionX\DiscountsEngine\Concerns;

use MissionX\DiscountsEngine\DataTransferObjects\Item;

trait HandlesLimitationToProducts
{
    /**
     * select the applicable products
     *
     * @var callable(Item $item): bool
     */
    protected $productSelector;

    /**
     * Applicable Items
     *
     * The Items that this discount shoule be applied to
     */
    protected array $items;

    protected array $applicableItems;

    public function limitToProducts(callable $productSelector): static
    {
        $this->productSelector = $productSelector;

        return $this;
    }

    /**
     * @param \MissionX\DiscountsEngine\Item[] $items
     */
    public function applyTo(array $items): static
    {
        $this->items = $items;

        $this->applicableItems = !isset($this->productSelector)
            ? $items
            : array_filter($items, $this->productSelector);

        return $this;
    }

    /**
     * Get the total of the applicable items
     */
    protected function getPurchaseAmount(): float
    {
        return array_reduce($this->applicableItems, fn(float $total, Item $item) => $total + $item->total(), 0.0);
    }

    /**
     * get the total qty of the applicable items
     */
    protected function getPurchaseQty(): int
    {
        return array_reduce($this->applicableItems, fn(float $total, Item $item) => $total + $item->qty, 0);
    }
}
