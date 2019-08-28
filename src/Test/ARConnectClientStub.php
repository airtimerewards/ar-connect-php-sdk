<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2019
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect\Test;

use AirtimeRewards\ARConnect\ARConnectClientInterface;
use AirtimeRewards\ARConnect\Client;
use AirtimeRewards\ARConnect\Credit;
use AirtimeRewards\ARConnect\CreditCollection;
use AirtimeRewards\ARConnect\CreditTypeCollection;
use AirtimeRewards\ARConnect\Eligibility;
use AirtimeRewards\ARConnect\Exception\FailedResponseException;
use AirtimeRewards\ARConnect\Hateoas\HateoasInterface;
use AirtimeRewards\ARConnect\Hateoas\PaginatedCollection;
use AirtimeRewards\ARConnect\Network;
use AirtimeRewards\ARConnect\NetworkCollection;
use Money\Money;

/**
 * This is a stub class for the AR Connect client to use in dev and test
 * environments.
 *
 * It accepts the same data as client does, but it does not process them.
 * Instead, it returns hard-coded example values.
 */
final class ARConnectClientStub implements ARConnectClientInterface
{
    public function getNetworks(?string $msisdn = null): NetworkCollection
    {
        return NetworkCollection::fromJsonArray(
            \GuzzleHttp\json_decode(
                \file_get_contents(
                    null === $msisdn
                        ? __DIR__.'/data/all_networks.json'
                        : __DIR__.'/data/filtered_networks.json'
                )
            )
        );
    }

    public function getCreditTypesForNetwork(Network $network): CreditTypeCollection
    {
        return $this->getCreditTypesForNetworkId($network->getId());
    }

    public function getCreditTypesForNetworkId(string $networkId): CreditTypeCollection
    {
        return CreditTypeCollection::fromJsonArray(
            \GuzzleHttp\json_decode(
                \file_get_contents(__DIR__.'/data/credit_types.json')
            )
        );
    }

    public function createCredit(
        string $msisdn,
        $network,
        string $subscriptionType,
        Money $value,
        bool $sendSmsConfirmation,
        ?string $clientReference = null
    ): Credit {
        $links = [
            'self' => [
                'href' => '/v1/credits/fac864d5-c4d9-4ebb-9668-4a57abb345bd',
            ],
            'environment' => [
                'href' => '/v1/environments/a3406c4f-d42f-4de9-83bd-d7610d4e7229',
            ],
        ];
        $networkId = ($network instanceof Network) ? $network->getId() : (string) $network;

        return new Credit(
            'fac864d5-c4d9-4ebb-9668-4a57abb345bd',
            $msisdn,
            $networkId,
            $subscriptionType,
            $value,
            false,
            null,
            null,
            $sendSmsConfirmation,
            false,
            Credit::STATUS_PENDING,
            new \DateTimeImmutable(),
            $clientReference,
            $links
        );
    }

    public function getCredits(): CreditCollection
    {
        return CreditCollection::fromJsonArray(
            \GuzzleHttp\json_decode(\file_get_contents(__DIR__.'/data/credit_collection.json'))
        );
    }

    public function getCredit(string $id): Credit
    {
        return Credit::fromJsonArray(
            \GuzzleHttp\json_decode(\file_get_contents(__DIR__.'/data/credit.json'))
        );
    }

    public function getPageByRel(string $rel, PaginatedCollection $collection): ?PaginatedCollection
    {
        return null;
    }

    public function getRefreshed(HateoasInterface $resource): HateoasInterface
    {
        return $resource;
    }

    public function getEligibilityForNetworkIds(string $msisdn, array $networkIds): array
    {
        $last = (int) \mb_substr($msisdn, -1);

        switch ($last % 3) {
            case 1:
                $eligibility = Eligibility::ELIGIBLE;
                break;
            case 2:
                $eligibility = Eligibility::INELIGIBLE;
                break;
            default:
                $eligibility = Eligibility::UNKNOWN;
        }

        $result = [];
        $i = 0;

        foreach ($networkIds as $index => $networkId) {
            $result[] = new Eligibility($eligibility, new Network($networkId, 'Network '.++$i));
        }

        return $result;
    }

    public function getEligibilityForNetworks(string $msisdn, iterable $networks): array
    {
        $networkIds = [];

        foreach ($networks as $network) {
            $networkIds[] = $network->getId();
        }

        return $this->getEligibilityForNetworkIds($msisdn, $networkIds);
    }
}
