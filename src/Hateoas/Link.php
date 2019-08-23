<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2019
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect\Hateoas;

final class Link
{
    /** @var string relation to resource */
    private $rel;

    /** @var string the URI to resource */
    private $href;

    public function __construct(string $rel, string $href)
    {
        $this->rel = $rel;
        $this->href = $href;
    }

    public function getRel(): string
    {
        return $this->rel;
    }

    public function getHref(): string
    {
        return $this->href;
    }

    public function __toString(): string
    {
        return $this->href;
    }
}
