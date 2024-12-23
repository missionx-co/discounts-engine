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

    public function __construct(public ?string $name = null) {}

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
                name: $this->name(),
                items: $this->items,
                savings: 0,
                error: $errorMessage
            );
        }

        $result = $this->calculateDiscount();

        // make sure to keep the original full list
        $result->items = $this->items;

        return $result;
    }

    public function name(): string
    {
        if ($this->name) {
            return $this->name;
        }

        return static::class;
    }

    public function __invoke(array $items): DiscountResult
    {
        return $this->applyTo($items)->calculate();
    }
}
