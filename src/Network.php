<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2019
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect;

use AirtimeRewards\ARConnect\Hateoas\HateoasInterface;
use AirtimeRewards\ARConnect\Hateoas\Traits\HateoasLinksTrait;

/**
 * AR Connect network instance.
 *
 * @author Jaik Dean <jaik@airtimerewards.com>
 */
final class Network implements HateoasInterface
{
    use HateoasLinksTrait;

    /**
     * @var string UUID string, for example '123e4567-e89b-12d3-a456-426655440000'
     */
    private $id;

    /**
     * @var string human-readable title
     */
    private $brand;

    /**
     * {@inheritdoc}
     *
     * @return Network
     */
    public static function fromJsonArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['brand'],
            $data['_links'] ?? []
        );
    }

    public function __construct(string $id, string $brand, array $links = [])
    {
        $this->id = $id;
        $this->brand = $brand;
        $this->populateLinks($links);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }
}
