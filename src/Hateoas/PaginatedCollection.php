<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2019
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect\Hateoas;

use AirtimeRewards\ARConnect\ARConnectClientInterface;
use AirtimeRewards\ARConnect\Exception\FailedResponseException;
use AirtimeRewards\ARConnect\Exception\InvalidResponseException;
use GuzzleHttp\Exception\GuzzleException;

abstract class PaginatedCollection extends HateoasCollection
{
    /** @var int */
    protected $page;

    /** @var int */
    protected $limit;

    /** @var int */
    protected $pages;

    /** @var int */
    protected $total;

    /** @var ARConnectClientInterface */
    private $client;

    public function __construct(array $data, ARConnectClientInterface $client)
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

    /**
     * @throws FailedResponseException
     * @throws InvalidResponseException
     * @throws \Throwable & GuzzleException
     *
     * @return static|null
     */
    protected function getPage(string $page)
    {
        return $this->client->getPageByRel($page, $this);
    }

    /**
     * @return static|null
     */
    abstract public function getNextPage();

    /**
     * @return static|null
     */
    abstract public function getPreviousPage();

    /**
     * @return static|null
     */
    abstract public function getFirstPage();

    /**
     * @return static|null
     */
    abstract public function getLastPage();
}
