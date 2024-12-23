<?php

namespace MissionX\DiscountsEngine\Concerns;

use Closure;
use MissionX\DiscountsEngine\Errors;
use MissionX\DiscountsEngine\Enums\DiscountType;
use MissionX\DiscountsEngine\Enums\DiscountPriority;

trait HandlesCombiningWithOtherDiscounts
{
    public bool $canCombineWithOtherDiscounts = false;

    public bool $forceCombineWithOtherDiscounts = false;

    /**
     * Higher value = higher priority
     */
    public DiscountPriority $priority = DiscountPriority::Normal;

    public function combineWithOtherDiscounts(bool $canCombineWithOtherDiscounts = true): static
    {
        $this->canCombineWithOtherDiscounts = $canCombineWithOtherDiscounts;

        return $this;
    }

    public function forceCombineWithOtherDiscounts(bool $forceCombineWithOtherDiscounts = true): static
    {
        $this->forceCombineWithOtherDiscounts = $forceCombineWithOtherDiscounts;

        return $this;
    }

    public function priority(DiscountPriority $priority): static
    {
        $this->priority = $priority;

        return $this;
    }
}
