<?php

namespace MissionX\DiscountsEngine\Concerns;

use MissionX\DiscountsEngine\Enums\DiscountType;

trait HasAmount
{
    public DiscountType $type;

    public float $amount;

    public function amount(float $amount, DiscountType $type = DiscountType::Percentage): static
    {
        $this->type = $type;
        $this->amount = $amount;
        return $this;
    }
}
