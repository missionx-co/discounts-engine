<?php

namespace MissionX\DiscountsEngine\Concerns;

use Closure;
use MissionX\DiscountsEngine\Errors;

trait HasMinPurchaseAmountRequirement
{
    public float $minPurchaseAmount = 0.0;

    public function minPurchaseAmount(float $amount): static
    {
        $this->minPurchaseAmount = $amount;

        return $this;
    }

    public function checkMinPurchaseAmountRequirement(Closure $fail)
    {
        if (! $this->minPurchaseAmount) {
            return;
        }

        if ($this->minPurchaseAmount <= ($total = $this->getPurchaseAmount())) {
            return;
        }

        $fail(
            Errors::get('min-purchase-violation', [
                '@subtotal' => number_format($total),
                '@minPurchaseAmount' => number_format($this->minPurchaseAmount),
            ])
        );
    }
}
