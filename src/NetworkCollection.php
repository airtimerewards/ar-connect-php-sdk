<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2019
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect;

use AirtimeRewards\ARConnect\Hateoas\HateoasCollection;
use AirtimeRewards\ARConnect\Hateoas\Traits\CollectionTrait;

final class NetworkCollection extends HateoasCollection
{
    use CollectionTrait;

    protected function populateEmbedded(array $embeddedData): void
    {
        if (!isset($embeddedData['networks'])) {
            throw new \InvalidArgumentException('Embedded data must contain networks');
        }

        foreach ($embeddedData['networks'] as $networkData) {
            $this->items[] = Network::fromJsonArray($networkData);
        }
    }

    public function current(): Network
    {
        return $this->items[$this->position];
    }
}
