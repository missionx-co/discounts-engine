<?php

namespace MissionX\DiscountsEngine\Tests\Discounts;

use MissionX\DiscountsEngine\DataTransferObjects\Item;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use MissionX\DiscountsEngine\Tests\HasTestItems;
use MissionX\DiscountsEngine\Discounts\AmountOffOrderDiscount;

class AmountOffOrderDiscountTest extends TestCase
{
    use HasTestItems;

    #[Test]
    public function it_apply_discount_that_is_limited_to_products_only()
    {
        $discountResult = (new AmountOffOrderDiscount())
            ->amount(20)
            ->limitToProducts(fn(Item $item) => $item->type == 'product')
            ->minPurchaseAmount(200)
            ->applyTo($this->items())
            ->calculate();

        $this->assertEquals(40, $discountResult->savings);
        $this->assertTrue($discountResult->wasApplied());

        foreach ($this->items() as $item) {
            $this->assertEquals(0, $item->discount);
        }
    }

    #[Test]
    public function it_does_not_apply_because_min_purchase_is_greater_than_purchase_amount()
    {
        $discountResult = (new AmountOffOrderDiscount())
            ->amount(20)
            ->limitToProducts(fn(Item $item) => $item->type == 'product')
            ->minPurchaseAmount(205)
            ->applyTo($this->items())
            ->calculate();

        $this->assertEquals(0, $discountResult->savings);
        $this->assertFalse($discountResult->wasApplied());
    }
}
