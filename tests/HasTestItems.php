<?php

namespace MissionX\DiscountsEngine\Tests;

use MissionX\DiscountsEngine\DataTransferObjects\Item;

trait HasTestItems
{
    private function items(): array
    {
        return once(fn () => [
            new Item(id: 1, name: 'Item 1', qty: 1, price: 100, type: 'product'),
            new Item(id: 2, name: 'Item 1', qty: 2, price: 50, type: 'product'),
            new Item(id: 3, name: 'Item 1', qty: 1, price: 5, type: 'shipping'),
        ]);
    }
}
