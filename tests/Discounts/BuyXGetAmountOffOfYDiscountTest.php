<?php

namespace MissionX\DiscountsEngine\Tests\Discounts;

use MissionX\DiscountsEngine\DataTransferObjects\Item;
use MissionX\DiscountsEngine\DataTransferObjects\YItem;
use MissionX\DiscountsEngine\Discounts\BuyXGetAmountOffOfYDiscount;
use MissionX\DiscountsEngine\Enums\DiscountType;
use MissionX\DiscountsEngine\Tests\HasTestItems;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BuyXGetAmountOffOfYDiscountTest extends TestCase
{
    use HasTestItems;

    #[Test]
    public function it_hancles_buy_2_of_item_2_and_get_50_percent_of_off_item_1()
    {
        $discount = (new BuyXGetAmountOffOfYDiscount)
            ->amount(50, DiscountType::Percentage)
            ->limitToItems(fn (Item $item) => $item->type == 'product')
            ->hasX(fn (Item $item) => $item->id == 2 & $item->qty == 2)
            ->getY(fn (array $items) => [new YItem(1)])
            ->applyTo($this->items())
            ->calculate();

        $this->assertEquals(50, $discount->savings);
        $this->assertTrue($discount->wasApplied());
        foreach ($this->items() as $item) {
            $this->assertEquals($item->id == 1 ? 50 : 0, $item->discount);
        }
    }

    #[Test]
    public function it_handles_free_shipping()
    {
        $discount = (new BuyXGetAmountOffOfYDiscount)
            ->amount(100, DiscountType::Percentage)
            ->limitToItems(fn (Item $item) => $item->type == 'product')
            ->minPurchaseAmount(200)
            ->getY(fn (array $items) => [new YItem(3)])
            ->applyTo($this->items())
            ->calculate();

        $this->assertEquals(5, $discount->savings);
        $this->assertTrue($discount->wasApplied());
        foreach ($this->items() as $item) {
            $this->assertEquals($item->id == 3 ? 5 : 0, $item->discount);
        }
    }
}
