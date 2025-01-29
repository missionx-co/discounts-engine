# Discounts Engine

A simple and developer-friendly tool for creating, managing, and validating discount rules in PHP applications.

## Installation

```bash
composer require missionx-co/discounts-engine
```

## Usage

This package revolves around three main entities that you'll work with:

### 1. Item

Convert your products into Item objects. An Item has the following attributes:

1. `id`: A unique identifier for the item.
2. `name`: The name of the item.
3. `qty`: The quantity of the item.
4. `price`: The unit price of the item.
5. `type`: The type of the item (default is unknown). You can use this to categorize items, e.g., product, shipping, or tax.
6. `discount`: The initial discount applied to the item. This value is updated as discounts are applied by the engine.

### 2. Discount

Define discount configurations to apply to your items.

**Example Items**

```php
$items = [
    new Item(id: 1, name: 'Item 1', qty: 1, price: 100, type: 'product'),
    new Item(id: 2, name: 'Item 1', qty: 2, price: 50, type: 'product'),
    new Item(id: 3, name: 'Item 1', qty: 1, price: 5, type: 'shipping'),
];
```

-   Total amount: `205`
-   Total quantity: `4`

#### 2.1 Basic Discount Configuration

-   `limitToItems`:Use this to specify which items the discount applies to. It accepts a callable to filter items.
    Example: Apply the discount only to items with type = product. In this case, the purchase amount will be 200 and the quantity will be 3.

    ```php
    (new Discount)
    ->limitToItems(
        fn (array $items) => array_filter(
            $items,
            fn (Item $item) => $item->type == 'product'
        )
    )
    ```

-   `priority`: When multiple discounts are processed, the engine will apply them in order of priority (highest to lowest).

    ```php
    use MissionX\DiscountsEngine\Enums\DiscountPriority;

    (new Discount)
        ->priority(DiscountPriority::High)
    ```

-   `minPurchaseAmount`: The discount applies only if the purchase amount is at least the specified minimum.

    ```php
    (new Discount)->minPurchaseAmount(50)
    ```

-   `minQty`: The discount applies only if the total quantity of applicable items meets or exceeds the minimum.

    ```php
    (new Discount)->minQty(4)
    ```

-   `combineWithOtherDiscounts`: Allow the discount to be combined with other discounts if the other discount allow combining as well.

    ```php
    (new Discount)->combineWithOtherDiscounts()
    ```

-   `forceCombineWithOtherDiscounts`: force the discount to be combined with other discounts even if the other discounts DOES NOT allow combining.

    ```php
    (new Discount)->forceCombineWithOtherDiscounts()
    ```

#### 2.2 Available Discount Types

The `Discount` class is abstract, so you cannot instantiate it directly. This package provides three types of discount objects to handle most use cases.

##### 2.2.1 Amount Off Product

This discount can apply to all applicable items or just a subset.

Example 1: Apply a %10 discount to all applicable items. The result of this discount is 20.

```php
use MissionX\DiscountsEngine\Enums\DiscountType;

// the following configuration will be applied to all applicable items
(new AmountOffProductDiscount)
    ->amount(10)
    ->limitToItems(
        fn (array $items) => array_filter(
            $items,
            fn (Item $item) => $item->type == 'product'
        )
    )
```

Example 2: Apply a $10 fixed discount to a specific item (e.g., item with id = 2) only if the purchase amount exceeds $200.

```php
// the following configuration will be applied to item with id=2 only
(new AmountOffProductDiscount)
    ->amount(10, DiscountType::FixedAmount)
    ->limitToItems(
        fn (array $items) => array_filter(
            $items,
            fn (Item $item) => $item->type == 'product'
        )
    )
    ->minPurchaseAmount(200)
    ->selectAffectedItemsUsing(
        fn (array $items) => array_filter(
            $items,
            fn (Item $item) => $item->id == 2
        )
    )
```

The result of the discount is 10

**Difference Between `limitToItems` and `selectAffectedItemsUsing`:**

-   `limitToItems` Filters which items are considered when calculating the purchase amount and quantity. For example, you can exclude shipping fees from the basket total.
-   `selectAffectedItemsUsing`: Specifies the exact items to which the discount will apply. For instance, if you provide a $10 fixed discount, you might apply it to only one specific item, not all applicable items.

In the example above:

-   The purchase amount is calculated based on the items filtered by limitToItems.
-   If the purchase amount exceeds $200, the discount is applied only to the items selected by `selectAffectedItemsUsing`.

##### 2.2.2 Amount Off Order

Apply a discount to the total purchase amount, considering any item limitations if specified.

```php
(new AmountOffOrderDiscount)
    ->amount(5)
    ->minPurchaseAmount(200)
    ->limitToItems(
        fn (array $items) => array_filter(
            $items,
            fn (Item $item) => $item->type == 'product'
        )
    )
```

In this example, the limitToItems method restricts the calculation to items of type product. As a result, the total purchase amount is 200, and the discount is 10 (5% of 200).

##### 2.2.3 Buy X Get Amount Off Y

This discount type applies a specified discount to certain items (`Y`) if the user purchases a specified items (`X`).

```php
(new BuyXGetAmountOffOfYDiscount())
    ->amount(50, DiscountType::Percentage)
    ->limitToItems(
        fn (array $items) => array_filter(
            $items,
            fn (Item $item) => $item->type == 'product'
        )
    )
    ->hasX(
        fn (array $applicableItems) => array_filter(
            $applicableItems,
            fn(Item $item) => $item->id == 2 && $item->qty == 2
        )
    )
    ->getY(fn(array $applicableItems, array $items, Discount $discount) => [new YItem(itemId: 1, qty: 1)])
```

In this example:

1. If the cart contains 2 units of the item with id=2, the user qualifies for the discount.
2. The discount is 50% off on 1 unit of the item with id=1. In case item id=1 has more than 1 item, the savings will be applied only to 1 unit
3. The `limitToItems` ensures that only items of type product are considered for eligibility and discount application.

You can also simulate free shipping with this discount by applying a 100% discount to the shipping item.

```php
(new BuyXGetAmountOffOfYDiscount())
    ->amount(100, DiscountType::Percentage)
    ->limitToItems(
        fn (array $items) => array_filter(
            $items,
            fn (Item $item) => $item->type == 'product'
        )
    )
    ->minPurchaseAmount(200)
    ->getY(fn(array $applicableItems, array $items, Discount $discount) => [new YItem(3)])
```

#### 2.3 Custom Discounts

You can create your own discount just by extending the `Discount` abstract class.

### 3. Discounts Engine

The final component to work with is the `DiscountsEngine`, which processes and applies discounts to items.

```php
$twentyPercentOff = (new AmountOffOrderDiscount('twenty percent off'))
    ->forceCombineWithOtherDiscounts()
    ->amount(20)
    ->limitToItems(
        fn (array $items) => array_filter(
            $items,
            fn (Item $item) => $item->type == 'product'
        )
    );

// This discount won't be applied because it will be evaluated after the "amount off product" discount,
// which reduces the purchase amount below 200.
$twentyPercentOffLimited = (new AmountOffOrderDiscount('twenty percent off, min purchase amount > 200'))
    ->forceCombineWithOtherDiscounts()
    ->amount(30)
    ->minPurchaseAmount(200)
    ->limitToItems(
        fn (array $items) => array_filter(
            $items,
            fn (Item $item) => $item->type == 'product'
        )
    );

$amountOffProduct  = (new AmountOffProductDiscount('10 percent off of all products'))
    ->priority(DiscountPriority::High)
    ->amount(10)
    ->limitToItems(
        fn (array $items) => array_filter(
            $items,
            fn (Item $item) => $item->type == 'product'
        )
    );

$engine = (new DiscountsEngine)
    ->addDiscount($twentyPercentOff)
    ->addDiscount($twentyPercentOffLimited)
    ->addDiscount($amountOffProduct)
    ->process($this->items());

$engine->totalBeforeDiscount(); // 205
$engine->savings(); // 56 (10% off all products from AmountOffProduct, followed by 20% off the remaining total)
$engine->total(); // 149
$engine->appliedDiscounts; // List of the applied discounts
```

**How does the Discount Engine apply discounts?**

1. The discount engine sorts discounts by priority (highest to lowest).
2. If the highest-priority discount can be combined with other discounts, all combinable discounts are applied.
3. If the highest-priority discount cannot be combined, only that discount and any discounts explicitly forced to combine will be applied.

This systematic approach ensures flexibility while maintaining order in how discounts are processed and applied.
