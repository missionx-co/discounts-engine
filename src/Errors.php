<?php

namespace MissionX\DiscountsEngine;

class Errors
{
    // default error messages
    public static array $messages = [
        'limited-products' => 'Coupon is restricted to specific products.',
        'min-purchase-violation' => "The coupon cannot be applied because the total of the applicable products (@subtotal) is below the minimum purchase requirement of @minPurchaseAmount.",
        'min-qty-violation' => "The coupon cannot be applied because the quantity of the applicable products (@quantity) is below the minimum required quantity of @minQuantity"
    ];

    public static function addErrorMessage(string $key, string $message)
    {
        self::$messages[$key] = $message;
    }

    /**
     * get the error message and replace placeholder within with message with the values
     */
    public static function get(string $key, $values = [])
    {
        $message = self::$messages[$key];
        return strtr($message, $values);
    }
}
