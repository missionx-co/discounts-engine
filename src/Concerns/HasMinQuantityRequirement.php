<?php

namespace MissionX\DiscountsEngine\Concerns;

use Closure;
use MissionX\DiscountsEngine\Errors;
use MissionX\DiscountsEngine\Enums\DiscountType;

trait HasMinQuantityRequirement
{
    public int $minQty = 0;

    public function minQty(int $amount): static
    {
        $this->minQty = $amount;

        return $this;
    }

    public function checkMinQuantityRequirement(Closure $fail)
    {
        if (!$this->minQty) {
            return;
        }

        if ($this->minQty <= ($total = $this->getPurchaseQty())) {
            return;
        }

        $fail(
            Errors::get('min-qty-violation', [
                '@quantity' => $total,
                '@minQuantity' => $this->minQty,
            ])
        );
    }
}
