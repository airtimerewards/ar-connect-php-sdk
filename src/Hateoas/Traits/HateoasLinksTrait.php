<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2019
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect\Hateoas\Traits;

use AirtimeRewards\ARConnect\Hateoas\Link;

/**
 * @author Rick Ogden <rick@airtimerewards.com>
 */
trait HateoasLinksTrait
{
    /**
     * @var Link[]
     */
    private $links = [];

    /**
     * @return Link[]
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * Get a link by its relation.
     *
     * @param string $rel the relation of resource e.g. "self"
     */
    public function getLink(string $rel): ?Link
    {
        return $this->links[$rel] ?? null;
    }

    protected function populateLinks(array $links): void
    {
        foreach ($links as $key => $link) {
            $rel = $link['rel'] ?? $key;
            $this->links[$rel] = new Link($rel, $link['href']);
        }
    }
}
