<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2019
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect\Test;

use AirtimeRewards\ARConnect\Client;
use AirtimeRewards\ARConnect\Credit;
use AirtimeRewards\ARConnect\Eligibility;
use AirtimeRewards\ARConnect\Exception\FailedResponseException;
use AirtimeRewards\ARConnect\Network;
use Money\Money;
use Psr\Log\LoggerInterface;

/**
 * This is a stub class for the AR Connect client to use in dev and test
 * environments.
 *
 * It accepts the same data as client does, but it does not process them.
 * Instead, it returns hard-coded example values.
 *
 * @author Rick Ogden <rick@airtimerewards.com>
 */
class ClientStub extends Client
{
    protected const PATH_GET_CREDIT_TYPES_FOR_NETWORK = '/v1/networks/networkId/credit-types';
    protected const PATH_GET_CREDIT = '/v1/credits/creditId';

    /**
     * @return ClientStub
     */
    public static function createClient(
        string $apiToken,
        string $environmentId,
        LoggerInterface $logger,
        string $endpoint = 'https://api.connect.airtimerewards.co.uk'
    ): Client {
        $client = new \GuzzleHttp\Client();

        return new self($client, $apiToken, $environmentId, $logger);
    }

    /**
     * {@inheritdoc}
     */
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
                'href' => '/v1/environments/'.$this->environmentId,
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

    protected function makeRequest(
        string $method,
        string $uri,
        array $options = [],
        array $logContext = []
    ): array {
        switch ($uri) {
            case self::PATH_GET_NETWORKS:
                if (isset($options['query']['msisdn'])) {
                    $json = \file_get_contents(__DIR__.'/data/filtered_networks.json');
                } else {
                    $json = \file_get_contents(__DIR__.'/data/all_networks.json');
                }
            break;
            case self::PATH_GET_CREDIT_TYPES_FOR_NETWORK:
                $json = \file_get_contents(__DIR__.'/data/credit_types.json');
            break;
            case self::PATH_CREATE_QUOTE:
                $json = \file_get_contents(__DIR__.'/data/quote.json');
            break;
            case self::PATH_GET_CREDIT:
                $json = \file_get_contents(__DIR__.'/data/credit.json');
            break;
            case self::PATH_CREATE_CREDIT:
                $json = \file_get_contents(__DIR__.'/data/credit_collection.json');
            break;
            default:
                throw new FailedResponseException('Not found.');
        }

        return \GuzzleHttp\json_decode($json, true);
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
        foreach ($networkIds as $index => $networkId) {
            $result[] = new Eligibility($eligibility, new Network($networkId, 'Network '.($index + 1)));
        }

        return $result;
    }
}
