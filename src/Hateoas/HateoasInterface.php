<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2019
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect\Hateoas;

/**
 * @author Rick Ogden <rick@airtimerewards.com>
 */
interface HateoasInterface
{
    /**
     * This creates a new instance of itself from a JSON decoded response.
     *
     * @param array $data decoded JSON in array format
     */
    public static function fromJsonArray(array $data);

    public function getLinks(): array;

    public function getLink(string $rel): ?Link;
}
