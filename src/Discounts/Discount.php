<?php

namespace MissionX\DiscountsEngine\Discounts;

use Closure;
use MissionX\DiscountsEngine\Concerns\HandlesCombiningWithOtherDiscounts;
use MissionX\DiscountsEngine\Concerns\HandlesLimitationToItems;
use MissionX\DiscountsEngine\Concerns\HasMinPurchaseAmountRequirement;
use MissionX\DiscountsEngine\Concerns\HasMinQuantityRequirement;
use MissionX\DiscountsEngine\DataTransferObjects\DiscountResult;
use MissionX\DiscountsEngine\Errors;

abstract class Discount
{
    use HandlesCombiningWithOtherDiscounts,
        HandlesLimitationToItems,
        HasMinPurchaseAmountRequirement,
        HasMinQuantityRequirement;

    private int $id;

    public bool $isAutomatic = false;

    public array $metadata = [];

    public function __construct(
        public ?string $name = null,
    ) {
        $this->id = mt_rand(100000, 999999);
    }

    /*-----------------------------------------------------
    * Methods
    -----------------------------------------------------*/
    /**
     * @param \MissionX\DiscountsEngine\Item[]
     */
    public function assertCanBeApplied(Closure $fail)
    {
        if (empty($this->applicableItems)) {
            $fail(Errors::get('limited-products'));

            return;
        }

        $this->checkMinPurchaseAmountRequirement($fail);
        $this->checkMinQuantityRequirement($fail);
    }

    abstract protected function calculateDiscount(): DiscountResult;

    /**
     * @param \MissionX\DiscountsEngine\Item[]
     */
    public function calculate(): DiscountResult
    {
        $errorMessage = null;
        $failed = false;
        $fail = function ($message) use (&$errorMessage, &$failed) {
            $errorMessage = $message;
            $failed = true;
        };

        $this->assertCanBeApplied($fail);

        if ($failed) {
            return new DiscountResult(
                discount: $this,
                savings: 0,
                error: $errorMessage
            );
        }

        $result = $this->calculateDiscount();

        return $result;
    }

    public function setMetadata(string $key, $value): static
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    public function setIsAutomatic(bool $isAutomatic = true): static
    {
        $this->isAutomatic = $isAutomatic;
        return $this;
    }

    public function name(): string
    {
        if ($this->name) {
            return $this->name;
        }

        return static::class;
    }

    public function id()
    {
        return $this->id;
    }
}
