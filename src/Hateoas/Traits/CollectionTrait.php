<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2019
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect\Hateoas\Traits;

/**
 * @author Rick Ogden <rick@airtimerewards.com>
 */
trait CollectionTrait
{
    /**
     * @var array<int,mixed>
     */
    protected $items = [];

    /**
     * Checks if current position is valid.
     */
    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }

    /**
     * Count elements of an object.
     */
    public function count(): int
    {
        return \count($this->items);
    }
}
