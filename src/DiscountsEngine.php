<?php

namespace MissionX\DiscountsEngine;

use MissionX\DiscountsEngine\Discounts\Discount;
use MissionX\DiscountsEngine\DataTransferObjects\Item;
use MissionX\DiscountsEngine\DataTransferObjects\DiscountResult;
use MissionX\DiscountsEngine\Exceptions\DiscountsGroupNotElectedException;

class DiscountsEngine
{
    public array $items = [];

    /**
     * The Discounts that will be applied to items
     *
     * @var \MissionX\DiscountsEngine\Discounts\Discount[]
     */
    public array $discounts = [];

    protected array $groups;

    public function addDiscount(Discount $discount): static
    {
        $this->discounts[] = $discount;

        return $this;
    }

    /**
     * Process Items and return the discount group that was elected to be applied
     */
    public function process(array $items): DiscountsGroup
    {
        $groups = $this->getDiscountsGroups();
        if (empty($groups)) {
            throw new DiscountsGroupNotElectedException("Discounts not found");
        }

        // find savings for all groups
        $savings = array_reduce(
            $groups,
            function (array $acc, DiscountsGroup $discountsGroup) use ($items) {
                $acc[$discountsGroup->id()] = $discountsGroup->process($items);
                return $acc;
            },
            []
        );

        // sort $savings desc
        uasort($savings, function ($a, $b) {
            if ($a == $b) {
                return 0;
            }

            return $a > $b ? -1 : 1;
        });

        return $groups[array_key_first($savings)];
    }

    protected function groupDiscounts()
    {
        if (count($this->discounts) == 0) {
            return [];
        }

        if (count($this->discounts) == 1) {
            $group = new DiscountsGroup($this->discounts);
            $this->groups = [$group->id() => $group];
            return;
        }

        $this->groups = [];
        foreach ($this->discounts as $discount) {
            if ($discount->forceCombineWithOtherDiscounts) {
                continue;
            }

            $group = new DiscountsGroup($this->getDiscountsThatCanBeCombinedWithDiscount($discount));
            if (isset($this->groups[$group->id()])) {
                continue;
            }

            $this->groups[$group->id()] = $group;
        }
    }

    protected function getDiscountsThatCanBeCombinedWithDiscount(Discount $discount): array
    {
        if (!$discount->canCombineWithOtherDiscounts) {
            return array_filter($this->discounts, fn(Discount $item) => $item == $discount || $item->forceCombineWithOtherDiscounts);
        }

        return array_filter($this->discounts, fn(Discount $item) => $item == $discount || $item->forceCombineWithOtherDiscounts || $item->canCombineWithOtherDiscounts);
    }

    public function getDiscountsGroups(): array
    {
        if (!isset($this->groups)) {
            $this->groupDiscounts();
        }

        return $this->groups;
    }
}
