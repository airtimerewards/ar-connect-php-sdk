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

/**
 * @author Rick Ogden <rick@airtimerewards.com>
 */
class CreditTypeCollection extends HateoasCollection
{
    use CollectionTrait;

    protected function populateEmbedded(array $embeddedData): void
    {
        if (!isset($embeddedData['credit_types'])) {
            throw new \InvalidArgumentException('Embedded data must contain credit_types');
        }

        foreach ($embeddedData['credit_types'] as $data) {
            $this->items[] = CreditType::fromJsonArray($data);
        }
    }

    public function current(): CreditType
    {
        return $this->items[$this->position];
    }
}
