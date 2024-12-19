<?php

namespace MissionX\DiscountsEngine;

class DiscountsEngine
{
    public array $items;

    public array $originalItems;

    public function __construct() {}

    public function process(array $items)
    {
        $this->items = $items;
        $this->originalItems = $items;
    }
}
