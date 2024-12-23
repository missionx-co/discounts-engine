<?php

namespace MissionX\DiscountsEngine\Tests;

use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use MissionX\DiscountsEngine\DiscountsEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use MissionX\DiscountsEngine\Discounts\Discount;
use MissionX\DiscountsEngine\Enums\DiscountPriority;
use MissionX\DiscountsEngine\DataTransferObjects\Item;
use MissionX\DiscountsEngine\Discounts\AmountOffOrderDiscount;
use MissionX\DiscountsEngine\Discounts\AmountOffProductDiscount;
use MissionX\DiscountsEngine\Discounts\BuyXGetAmountOffOfYDiscount;

class DiscountsEngineTest extends TestCase
{
    use HasTestItems;

    #[Test]
    public function it_clones_items()
    {
        $this->engine()->setItems($this->items());
        $originalItemsById = $this->keyById($this->engine()->originalItems);
        $itemsById = $this->keyById($this->engine()->items);
        foreach ($this->items() as $item) {
            $this->assertSame($item, $originalItemsById[$item->id]);
            $this->assertNotSame($item, $itemsById[$item->id]);
        }
    }

    #[Test]
    public function it_sorts_discounts_by_priority()
    {
        $this->engine()
            ->addDiscount($orderOffDiscount = new AmountOffOrderDiscount())
            ->addDiscount(($productOffDiscount = new AmountOffProductDiscount())->priority(DiscountPriority::High))
            ->addDiscount(($buxXGetYDiscount = new BuyXGetAmountOffOfYDiscount())->priority(DiscountPriority::Low));

        invade($this->engine())->sortDiscountsByPriority();

        $this->assertSame($productOffDiscount, $this->engine()->discounts[0]);
        $this->assertSame($orderOffDiscount, $this->engine()->discounts[1]);
        $this->assertSame($buxXGetYDiscount, $this->engine()->discounts[2]);
    }

    #[Test]
    #[DataProvider('discountsDataProvider')]
    public function it_determine_the_discounts_that_should_be_applied($factory)
    {
        [$discounts, $expectedAppliedDiscount] = call_user_func($factory);
        foreach ($discounts as $discount) {
            $this->engine()->addDiscount($discount);
        }

        $discountsToBeApplied = array_values(
            invade($this->engine())->determineDiscountsThatShouldBeApplied()
        );
        $this->assertCount(count($expectedAppliedDiscount), $discountsToBeApplied);

        foreach ($expectedAppliedDiscount as $key => $appliedDiscount) {
            $this->assertSame($appliedDiscount, $discountsToBeApplied[$key]);
        }
    }

    public static function discountsDataProvider()
    {
        return [
            'Only the highest priority discount will be applied because other discounts are not forced to combine' => [
                'factory' => function () {
                    $discounts = [
                        (new AmountOffProductDiscount())->priority(DiscountPriority::High),
                        (new AmountOffOrderDiscount())->priority(DiscountPriority::High),
                        (new BuyXGetAmountOffOfYDiscount())->priority(DiscountPriority::High),
                    ];

                    return [$discounts, [$discounts[0]]];
                }
            ],
            'highest priority discount will be applied with the forced discount' => [
                'factory' => function () {
                    $discounts = [
                        (new AmountOffProductDiscount())->priority(DiscountPriority::High),
                        (new AmountOffOrderDiscount()),
                        (new BuyXGetAmountOffOfYDiscount())->forceCombineWithOtherDiscounts(),
                    ];

                    return [
                        $discounts,
                        [$discounts[0], $discounts[2]]
                    ];
                }
            ],
            'highest priority discount will be applied with the forced discount and the can be combined discount' => [
                'factory' => function () {
                    $discounts = [
                        (new AmountOffProductDiscount()),
                        (new AmountOffOrderDiscount())->priority(DiscountPriority::High)->combineWithOtherDiscounts(),
                        (new BuyXGetAmountOffOfYDiscount())->combineWithOtherDiscounts(),
                        (new BuyXGetAmountOffOfYDiscount())->forceCombineWithOtherDiscounts(),
                    ];

                    return [
                        $discounts,
                        [$discounts[1], $discounts[2], $discounts[3]]
                    ];
                }
            ],
        ];
    }

    #[Test]
    public function it_works()
    {
        $twentyPercentOff = (new AmountOffOrderDiscount('twenty percent off'))
            ->forceCombineWithOtherDiscounts()
            ->amount(20)
            ->limitToProducts(fn(Item $item) => $item->type == 'product');

        // this one won't be applied because it'll be applied after amount off product which will make the purchase amount less than 200
        $twentyPercentOffLimited = (new AmountOffOrderDiscount('twenty percent off, min purcahse amount > 200'))
            ->forceCombineWithOtherDiscounts()
            ->amount(20)
            ->minPurchaseAmount(200)
            ->limitToProducts(fn(Item $item) => $item->type == 'product');

        $amountOffProduct  = (new AmountOffProductDiscount('10 percent off of all products'))
            ->priority(DiscountPriority::High)
            ->amount(10)
            ->limitToProducts(fn(Item $item) => $item->type == 'product');

        $engine = (new DiscountsEngine)
            ->addDiscount($twentyPercentOff)
            ->addDiscount($twentyPercentOffLimited)
            ->addDiscount($amountOffProduct)
            ->process($this->items());

        $this->assertEquals(56, $engine->savings());
    }

    private function engine(): MockInterface|DiscountsEngine
    {
        return once(
            fn() => Mockery::mock(DiscountsEngine::class, function (MockInterface $mock) {
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
