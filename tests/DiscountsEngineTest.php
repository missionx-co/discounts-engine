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

class DiscountsEngineTest extends TestCase
{
    use HasTestItems;

    #[Test]
    public function it_finds_discounts_that_can_combine_with_a_discount()
    {
        $discount = new AmountOffOrderDiscount();
        $discount2 = (new AmountOffOrderDiscount())->forceCombineWithOtherDiscounts();
        $discount3 = (new AmountOffOrderDiscount())->combineWithOtherDiscounts();

        $this->engine()->addDiscount($discount)
            ->addDiscount($discount2)
            ->addDiscount($discount3);

        $discounts = invade($this->engine())->getDiscountsThatCanBeCombinedWithDiscount($discount);
        $this->assertCount(2, $discounts);
        $this->assertContains($discount, $discounts);
        $this->assertContains($discount2, $discounts);

        $discounts = invade($this->engine())->getDiscountsThatCanBeCombinedWithDiscount($discount3);
        $this->assertCount(2, $discounts);
        $this->assertContains($discount3, $discounts);
        $this->assertContains($discount2, $discounts);

        $discounts = invade($this->engine())->getDiscountsThatCanBeCombinedWithDiscount($discount2);
        $this->assertCount(1, $discounts);
        $this->assertContains($discount2, $discounts);
    }

    #[Test]
    public function it_groups_discounts()
    {
        $discount = new AmountOffOrderDiscount();
        $discount2 = (new AmountOffOrderDiscount())->forceCombineWithOtherDiscounts();
        $discount3 = (new AmountOffOrderDiscount())->combineWithOtherDiscounts();

        $this->engine()
            ->addDiscount($discount)
            ->addDiscount($discount2)
            ->addDiscount($discount3);

        $groups = $this->engine()->getDiscountsGroups();
        $this->assertCount(2, $groups);

        $key = array_key_first($groups);
        $this->assertContains($discount, $groups[$key]->discounts);
        $this->assertContains($discount2, $groups[$key]->discounts);

        $key = array_key_last($groups);
        $this->assertContains($discount3, $groups[$key]->discounts);
        $this->assertContains($discount2, $groups[$key]->discounts);
    }

    #[Test]
    public function it_works()
    {
        $discount = (new AmountOffOrderDiscount())->amount(20);
        $discount2 = (new AmountOffOrderDiscount())->amount(10)->forceCombineWithOtherDiscounts();
        $discount3 = (new AmountOffOrderDiscount())->amount(30)->combineWithOtherDiscounts();

        $this->engine()
            ->addDiscount($discount)
            ->addDiscount($discount2)
            ->addDiscount($discount3);

        $group = $this->engine()->process($this->items());

        // group with the higher discount was applied
        $this->assertEquals(82, $group->savings());
        $this->assertContains($discount2, $group->discounts);
        $this->assertContains($discount3, $group->discounts);
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
}
