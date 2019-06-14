<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2019
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect;

class Eligibility
{
    public const ELIGIBLE = 'ELIGIBLE';
    public const INELIGIBLE = 'INELIGIBLE';
    public const UNKNOWN = 'UNKNOWN';

    /**
     * @var string
     */
    private $eligible;

    /**
     * @var Network
     */
    private $network;

    public static function fromJsonArray(array $data): self
    {
        return new self($data['eligibility'], Network::fromJsonArray($data['network']));
    }

    public function __construct(string $eligible, Network $network)
    {
        $this->eligible = $eligible;
        $this->network = $network;
    }

    public function getEligible(): string
    {
        return $this->eligible;
    }

    public function getNetwork(): Network
    {
        return $this->network;
    }
}
