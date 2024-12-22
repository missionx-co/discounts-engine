<?php

namespace MissionX\DiscountsEngine\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use MissionX\DiscountsEngine\AmountOffProductDiscount;
use MissionX\DiscountsEngine\DataTransferObjects\Item;
use MissionX\DiscountsEngine\DataTransferObjects\YItem;
use MissionX\DiscountsEngine\Discounts\BuyXGetAmountOffOfYDiscount;
use MissionX\DiscountsEngine\Discounts\Discount;

class ExamplesTest extends TestCase
{
    use HasTestItems;

    #[Test]
    public function it_works()
    {
        $discount = (new BuyXGetAmountOffOfYDiscount)
            ->amount(100)
            ->limitToProducts(fn($item) => $item->type != 'shipping')
            ->minQty(3)
            ->getY(fn($items, Discount $discount) => [new YItem(3)])
            ->applyTo($this->items())
            ->calculate();
    }
}
