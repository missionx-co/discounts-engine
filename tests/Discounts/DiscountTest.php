<?php

namespace MissionX\DiscountsEngine\Tests\Discounts;

use MissionX\DiscountsEngine\DataTransferObjects\Item;
use MissionX\DiscountsEngine\Discounts\Discount;
use MissionX\DiscountsEngine\Tests\HasTestItems;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DiscountTest extends TestCase
{
    use HasTestItems;

    #[Test]
    #[DataProvider('productsDataProvider')]
    public function it_calculates_min_purchase_amount($products, $expectedAmount, $expectedQty)
    {
        $discount = invade(
            $this->discount()
                ->limitToItems($products)
                ->applyTo($this->items())
        );

        $this->assertEquals($expectedAmount, $discount->getPurchaseAmount());
        $this->assertEquals($expectedQty, $discount->getPurchaseQty());
    }

    public static function productsDataProvider()
    {
        return [
            'discount is not limited to any products' => [
                'products' => fn (Item $item) => true,
                'expectedAmount' => 205,
                'expectedQty' => 4,
            ],
            'discount is limited to products only' => [
                'products' => fn (Item $item) => $item->type == 'product',
                'expectedAmount' => 200,
                'expectedQty' => 3,
            ],
            'discount is limited to specific product' => [
                'products' => fn (Item $item) => $item->id == 1,
                'expectedAmount' => 100,
                'expectedQty' => 1,
            ],
        ];
    }

    #[Test]
    #[DataProvider('canBeAppliedDataProvider')]
    public function it_asserts_it_can_be_applied($canBeApplied, $minPurchaseAmount, $minQty)
    {
        $discount = $this->discount()
            ->limitToItems(fn (Item $item) => $item->type == 'product')
            ->minPurchaseAmount($minPurchaseAmount)
            ->minQty($minQty)
            ->applyTo($this->items());

        $failed = false;
        $fail = function () use (&$failed) {
            $failed = true;
        };

        $discount->assertCanBeApplied($fail);

        $this->assertEquals($canBeApplied, ! $failed);
    }

    public static function canBeAppliedDataProvider()
    {
        return [
            'can not be applied because min purcahse is greater than purchase amount' => [
                'canBeApplied' => false,
                'minPurchaseAmount' => 205,
                'minQty' => 0,
            ],
            'can be applied because min purcahse is greater than purchase amount' => [
                'canBeApplied' => true,
                'minPurchaseAmount' => 200,
                'minQty' => 0,
            ],
            'can not be applied because min qty is greater than purchase qty' => [
                'canBeApplied' => false,
                'minPurchaseAmount' => 0,
                'minQty' => 4,
            ],
            'can be applied because min qty is greater than purchase qty' => [
                'canBeApplied' => true,
                'minPurchaseAmount' => 0,
                'minQty' => 3,
            ],
        ];
    }

    public function discount(): Discount|MockInterface
    {
        return once(function () {
            $discount = Mockery::mock(Discount::class);
            $discount->makePartial();
            $discount->shouldAllowMockingProtectedMethods();

            return $discount;
        });
    }
}
