<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2018
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect;

use AirtimeRewards\ARConnect\Hateoas\PaginatedCollection;
use AirtimeRewards\ARConnect\Hateoas\Traits\CollectionTrait;

/**
 * @author Rick Ogden <rick@airtimerewards.com>
 */
class CreditCollection extends PaginatedCollection
{
    use CollectionTrait;

    protected function populateEmbedded(array $embeddedData): void
    {
        if (!isset($embeddedData['credits'])) {
            throw new \InvalidArgumentException('Embedded data must contain credits');
        }

        foreach ($embeddedData['credits'] as $data) {
            $this->items[] = Credit::fromJsonArray($data);
        }
    }

    public function current(): Credit
    {
        return $this->items[$this->position];
    }

    public function getNextPage(): ?self
    {
        return $this->getPage('next');
    }

    public function getPreviousPage(): ?self
    {
        return $this->getPage('previous');
    }

    public function getFirstPage(): ?self
    {
        return $this->getPage('first');
    }

    public function getLastPage(): ?self
    {
        return $this->getPage('last');
    }
}
