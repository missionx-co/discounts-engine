<?php

namespace MissionX\DiscountsEngine\Tests;

use MissionX\DiscountsEngine\DataTransferObjects\Item;
use MissionX\DiscountsEngine\Discounts\AmountOffOrderDiscount;
use MissionX\DiscountsEngine\Discounts\AmountOffProductDiscount;
use MissionX\DiscountsEngine\Discounts\BuyXGetAmountOffOfYDiscount;
use MissionX\DiscountsEngine\DiscountsEngine;
use MissionX\DiscountsEngine\DiscountsGroup;
use MissionX\DiscountsEngine\Enums\DiscountPriority;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DiscountsGroupTest extends TestCase
{
    use HasTestItems;

    #[Test]
    public function it_sorts_discounts_setting_automatic_discounts_first()
    {
        $this->group()->discounts = [
            $orderOffDiscount = new AmountOffOrderDiscount('Amount Off Order'),
            ($productOffDiscount = new AmountOffProductDiscount)->priority(DiscountPriority::High),
            ($buxXGetYDiscount = new BuyXGetAmountOffOfYDiscount)->priority(DiscountPriority::Low),
            ($orderOffDiscount2 = new AmountOffOrderDiscount('Amount Off Order'))->setIsAutomatic()->priority(DiscountPriority::Low)
        ];

        invade($this->group())->sortDiscounts();

        $this->assertSame($orderOffDiscount2, $this->group()->discounts[0]);
        $this->assertSame($productOffDiscount, $this->group()->discounts[1]);
        $this->assertSame($orderOffDiscount, $this->group()->discounts[2]);
        $this->assertSame($buxXGetYDiscount, $this->group()->discounts[3]);
    }

    #[Test]
    public function it_generates_ids()
    {
        $this->group()->discounts = [
            $orderOffDiscount = new AmountOffOrderDiscount('Amount Off Order'),
            ($productOffDiscount = new AmountOffProductDiscount)->priority(DiscountPriority::High),
            ($buxXGetYDiscount = new BuyXGetAmountOffOfYDiscount)->priority(DiscountPriority::Low),
            ($orderOffDiscount2 = new AmountOffOrderDiscount('Amount Off Order'))->setIsAutomatic()->priority(DiscountPriority::Low)
        ];

        invade($this->group())->sortDiscounts();

        $this->assertEquals(
            implode("", [
                $orderOffDiscount2->id(),
                $productOffDiscount->id(),
                $orderOffDiscount->id(),
                $buxXGetYDiscount->id()
            ]),
            invade($this->group())->generateId()
        );
    }

    #[Test]
    public function it_works()
    {
        $twentyPercentOff = (new AmountOffOrderDiscount('twenty percent off'))
            ->forceCombineWithOtherDiscounts()
            ->amount(20)
            ->limitToItems(
                fn(array $items) => array_filter(
                    $items,
                    fn(Item $item) => $item->type == 'product'
                )
            );

        // this one won't be applied because it'll be applied after amount off product which will make the purchase amount less than 200
        $twentyPercentOffLimited = (new AmountOffOrderDiscount('twenty percent off, min purcahse amount > 200'))
            ->forceCombineWithOtherDiscounts()
            ->amount(20)
            ->minPurchaseAmount(200)
            ->limitToItems(
                fn(array $items) => array_filter(
                    $items,
                    fn(Item $item) => $item->type == 'product'
                )
            );

        $amountOffProduct = (new AmountOffProductDiscount('10 percent off of all products'))
            ->priority(DiscountPriority::High)
            ->amount(10)
            ->limitToItems(
                fn(array $items) => array_filter(
                    $items,
                    fn(Item $item) => $item->type == 'product'
                )
            );

        $group = (new DiscountsGroup([
            $twentyPercentOff,
            $twentyPercentOffLimited,
            $amountOffProduct
        ]));

        $this->assertEquals(56, $group->process($this->items()));
        $this->assertTrue($group->hasAnyProcessedDiscountsApplied());
        $this->assertFalse($group->hasAllProcessedDiscountsApplied());
    }

    private function group(): MockInterface|DiscountsGroup
    {
        return once(
            fn() => Mockery::mock(DiscountsGroup::class, function (MockInterface $mock) {
                $mock->makePartial();
                $mock->shouldAllowMockingProtectedMethods();
            })
        );
    }

    private function keyById(array $items)
    {
        $map = [];
        foreach ($items as $item) {
            $map[$item->id] = $item;
        }

        return $map;
    }
}
