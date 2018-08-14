<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2018
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect\Hateoas;

use AirtimeRewards\ARConnect\Client;

/**
 * @author Rick Ogden <rick@airtimerewards.com>
 */
abstract class PaginatedCollection extends HateoasCollection
{
    /**
     * @var int
     */
    protected $page;

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $pages;

    /**
     * @var int
     */
    protected $total;

    /**
     * @var Client
     */
    private $client;

    public function __construct(array $data, Client $client)
    {
        parent::__construct($data);
        $this->client = $client;
        $this->page = $data['page'] ?? null;
        $this->limit = $data['limit'] ?? null;
        $this->pages = $data['pages'] ?? null;
        $this->total = $data['total'] ?? null;
    }

    public function getPageNumber(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getPages(): int
    {
        return $this->pages;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    protected function getPage(string $page)
    {
        return $this->client->getPageByRel($page, $this);
    }

    abstract public function getNextPage();

    abstract public function getPreviousPage();

    abstract public function getFirstPage();

    abstract public function getLastPage();
}
