<?php

namespace MissionX\DiscountsEngine\Tests\Discounts;

use MissionX\DiscountsEngine\DataTransferObjects\Item;
use MissionX\DiscountsEngine\Discounts\AmountOffProductDiscount;
use MissionX\DiscountsEngine\Enums\DiscountType;
use MissionX\DiscountsEngine\Tests\HasTestItems;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AmountOffProductDiscountTest extends TestCase
{
    use HasTestItems;

    #[Test]
    public function it_apply_discount_that_is_limited_to_products_only()
    {
        $discountResult = (new AmountOffProductDiscount)
            ->amount(20)
            ->limitToItems(
                fn(array $items) => array_filter(
                    $items,
                    fn(Item $item) => $item->type == 'product'
                )
            )
            ->minPurchaseAmount(200)
            ->applyTo($this->items())
            ->calculate();

        $this->assertEquals(40, $discountResult->savings);
        $this->assertTrue($discountResult->wasApplied());

        foreach ($this->items() as $item) {
            $this->assertEquals($item->type == 'product' ? 20 : 0, $item->discount);
        }
    }

    #[Test]
    public function it_apply_discount_to_a_specific_product_only()
    {
        $discountResult = (new AmountOffProductDiscount)
            ->amount(20, DiscountType::FixedAmount)
            ->limitToItems(
                fn(array $items) => array_filter(
                    $items,
                    fn(Item $item) => $item->type == 'product'
                )
            )
            ->selectAffectedItemsUsing(
                fn(array $items) => array_filter(
                    $items,
                    fn(Item $item) => $item->id == 2
                )
            )
            ->minPurchaseAmount(200)
            ->applyTo($this->items())
            ->calculate();

        // 40 because item 2 has qty=2
        $this->assertEquals(40, $discountResult->savings);
        $this->assertTrue($discountResult->wasApplied());

        foreach ($this->items() as $item) {
            $this->assertEquals($item->id == 2 ? 40 : 0, $item->discount);
        }
    }
}
