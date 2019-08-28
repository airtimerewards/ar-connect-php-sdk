<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2019
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect;

use AirtimeRewards\ARConnect\Exception\FailedResponseException;
use AirtimeRewards\ARConnect\Exception\InvalidResponseException;
use AirtimeRewards\ARConnect\Exception\UnrefreshableException;
use AirtimeRewards\ARConnect\Hateoas\HateoasInterface;
use AirtimeRewards\ARConnect\Hateoas\PaginatedCollection;
use GuzzleHttp\Exception\GuzzleException;
use Money\Money;

/**
 * AR Connect API client interface.
 */
interface ARConnectClientInterface
{
    /**
     * Get credit types available for a MSISDN (mobile number).
     *
     *
     * @param string $msisdn
     *
     * @throws FailedResponseException
     * @throws InvalidResponseException
     * @throws \Throwable & GuzzleException
     */
    public function getNetworks(?string $msisdn = null): NetworkCollection;

    /**
     * Get credit types available for a network.
     *
     * @param Network $network The network
     *
     * @throws FailedResponseException
     * @throws InvalidResponseException
     * @throws \Throwable & GuzzleException
     */
    public function getCreditTypesForNetwork(Network $network): CreditTypeCollection;

    /**
     * Get credit types available for a network ID.
     *
     * @throws FailedResponseException
     * @throws InvalidResponseException
     * @throws \Throwable & GuzzleException
     */
    public function getCreditTypesForNetworkId(string $networkId): CreditTypeCollection;

    /**
     * Send credit to a MSISDN (mobile number).
     *
     * @param Network|string $network Network object or Network ID
     *
     * @throws FailedResponseException
     * @throws InvalidResponseException
     * @throws \Throwable & GuzzleException
     */
    public function createCredit(
        string $msisdn,
        $network,
        string $subscriptionType,
        Money $value,
        bool $sendSmsConfirmation,
        ?string $clientReference = null
    ): Credit;

    public function getCredits(): CreditCollection;

    /**
     * Get a credit instance.
     *
     * @throws FailedResponseException
     * @throws InvalidResponseException
     * @throws \Throwable & GuzzleException
     */
    public function getCredit(string $id): Credit;

    /**
     * @throws FailedResponseException
     * @throws \Throwable & GuzzleException
     * @throws InvalidResponseException
     */
    public function getPageByRel(string $rel, PaginatedCollection $collection): ?PaginatedCollection;

    /**
     * This will take any resource or collection and get the latest version of it.
     *
     * @throws FailedResponseException
     * @throws InvalidResponseException
     * @throws UnrefreshableException
     * @throws \Throwable & GuzzleException
     */
    public function getRefreshed(HateoasInterface $resource): HateoasInterface;

    /**
     * @param string[] $networkIds
     *
     * @throws \Throwable & GuzzleException
     * @throws InvalidResponseException
     * @throws FailedResponseException
     *
     * @return Eligibility[]
     */
    public function getEligibilityForNetworkIds(string $msisdn, array $networkIds): array;

    /**
     * @param iterable<Network> $networks
     *
     * @throws \Throwable & GuzzleException
     * @throws InvalidResponseException
     * @throws FailedResponseException
     *
     * @return Eligibility[]
     */
    public function getEligibilityForNetworks(string $msisdn, iterable $networks): array;
}
