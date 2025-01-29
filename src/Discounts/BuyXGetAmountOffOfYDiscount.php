<?php

namespace MissionX\DiscountsEngine\Discounts;

use Closure;
use MissionX\DiscountsEngine\Concerns\HasAmount;
use MissionX\DiscountsEngine\DataTransferObjects\DiscountResult;
use MissionX\DiscountsEngine\DataTransferObjects\Item;
use MissionX\DiscountsEngine\DataTransferObjects\YItem;
use MissionX\DiscountsEngine\Errors;

class BuyXGetAmountOffOfYDiscount extends Discount
{
    use HasAmount;

    protected $hasX;

    /**
     * return the items that should be provided for free
     *
     * callable(applicableItems Item[], allItems Item[], Discount): YItem[]
     *
     * @var callable(Item[], Item[], Discount): YItem[]
     */
    protected $getY;

    public function __construct(public ?string $name = null)
    {
        parent::__construct($name);
        Errors::addErrorMessage('buy-x-get-y', 'The coupon can not be applied because you do not have the required items to qualify for the offer.');
    }

    /**
     * @param  callable(array $items): bool  $hasX  all provided items will be checked with this selector
     */
    public function hasX(callable $hasX): static
    {
        $this->hasX = $hasX;

        return $this;
    }

    /**
     * @param  callable(Item[] $applicableItems, Item[] $allItems, Discount $discount): YItem[]  $getY
     */
    public function getY(callable $getY): static
    {
        $this->getY = $getY;

        return $this;
    }

    public function assertCanBeApplied(Closure $fail)
    {
        parent::assertCanBeApplied($fail);

        if (! isset($this->hasX)) {
            return;
        }

        $items = call_user_func($this->hasX, $this->applicableItems);
        if (! empty($items)) {
            return;
        }

        $fail(Errors::get('buy-x-get-y'));
    }

    public function calculateDiscount(): DiscountResult
    {
        $yItems = call_user_func($this->getY, $this->applicableItems, $this->items, $this);

        $totalSavings = 0;
        foreach ($yItems as $yItem) {
            $item = $this->findById($yItem->itemId);
            $unitPriceWithRespectToDiscount = $item->total() / $item->qty;
            $savings = $this->type->calculateDiscountAmount($unitPriceWithRespectToDiscount, $this->amount) * $yItem->qty;
            $item->discount += $savings;
            $totalSavings += $savings;
        }

        return new DiscountResult(
            name: $this->name(),
            items: $this->items,
            savings: $totalSavings,
        );
    }

    protected function findById($id): Item
    {
        foreach ($this->items as $item) {
            if ($item->id == $id) {
                return $item;
            }
        }
    }
}
