<?php

namespace MissionX\DiscountsEngine\Concerns;

use Closure;
use MissionX\DiscountsEngine\Errors;
use MissionX\DiscountsEngine\Enums\DiscountType;

trait HandlesCombiningWithOtherDiscounts
{
    public bool $canCombineWithOtherDiscounts = false;

    public bool $forceCombineWithOtherDiscounts = false;

    /**
     * Higher value = higher priority
     */
    public int $priority = 0;

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

    public function priority(int $priority = 0): static
    {
        $this->priority = $priority;

        return $this;
    }
}
