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
use AirtimeRewards\ARConnect\Util\MoneyConverter;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Money\Money;
use Psr\Log\LoggerInterface;

/**
 * AR Connect API client.
 */
final class ARConnectClient implements ARConnectClientInterface
{
    private const PATH_CREATE_CREDIT = '/v1/environments/{environment}/credits';
    private const PATH_GET_CREDIT = '/v1/credits/{id}';
    private const PATH_GET_ELIGIBILITY = '/v1/environments/{environment}/eligibility/{msisdn}';
    private const PATH_GET_NETWORKS = '/v1/networks';
    private const PATH_GET_CREDIT_TYPES_FOR_NETWORK = '/v1/networks/{network}/credit-types';

    private const LOG_REQUEST_CREATE_CREDIT = '-> POST '.self::PATH_CREATE_CREDIT;
    private const LOG_REQUEST_GET_CREDITS = '-> GET '.self::PATH_CREATE_CREDIT;
    private const LOG_REQUEST_GET_CREDIT = '-> GET '.self::PATH_GET_CREDIT;
    private const LOG_REQUEST_GET_CREDIT_TYPES_FOR_MSISDN = '-> GET '.self::PATH_GET_NETWORKS;
    private const LOG_REQUEST_GET_CREDIT_TYPES_FOR_NETWORK = '-> GET '.self::PATH_GET_CREDIT_TYPES_FOR_NETWORK;
    private const LOG_RESPONSE_ERROR = '<- Error response received from AR Connect';
    private const LOG_RESPONSE_INVALID_JSON = '<- Invalid JSON received from AR Connect';
    private const LOG_RESPONSE_MISSING_DATA = '<- Missing data received from AR Connect';
    private const LOG_RESPONSE_OK = '<- Response OK';

    /** @var string API token */
    private $apiKey;

    /** @var ClientInterface */
    private $client;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $environmentId;

    /**
     * Intialise the Guzzle client.
     */
    public static function createClient(
        string $apiKey,
        string $environmentId,
        LoggerInterface $logger,
        string $endpoint = 'https://api.connect.airtimerewards.co.uk'
    ): self {
        $client = new Client(['base_uri' => $endpoint]);

        return new self($client, $apiKey, $environmentId, $logger);
    }

    /**
     * @param string $apiKey        API token
     * @param string $environmentId The environment for the API token
     */
    public function __construct(
        ClientInterface $client,
        string $apiKey,
        string $environmentId,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->apiKey = $apiKey;
        $this->logger = $logger;
        $this->environmentId = $environmentId;
    }

    public function getNetworks(?string $msisdn = null): NetworkCollection
    {
        // Log the request
        $logContext = $this->createLogContext();
        $this->logger->info(self::LOG_REQUEST_GET_CREDIT_TYPES_FOR_MSISDN, $logContext);

        // Make the API call
        $options = (null !== $msisdn) ? ['query' => ['msisdn' => $msisdn]] : [];

        $data = $this->makeRequest('GET', self::PATH_GET_NETWORKS, $options, $logContext);

        // Log a valid response
        $this->logger->info(self::LOG_RESPONSE_OK, $logContext);

        return new NetworkCollection($data);
    }

    private function createLogContext(): array
    {
        return ['request_id' => \uniqid('', true)];
    }

    /**
     * Make an actual API request.
     *
     * @param string $method     HTTP method
     * @param string $uri        URI
     * @param array  $options    Guzzle request options
     * @param array  $logContext Log context
     *
     * @throws FailedResponseException  if the request failed in an expected way
     * @throws InvalidResponseException if the response couldn’t be parsed
     * @throws \Throwable & GuzzleException
     *
     * @return array Decoded JSON body
     */
    private function makeRequest(
        string $method,
        string $uri,
        array $options = [],
        array $logContext = []
    ): array {
        $options['headers'] = \array_merge(
            $options['headers'] ?? [],
            ['Authorization' => 'Bearer '.$this->apiKey]
        );
        $options['http_errors'] = false;
        $uri = \str_replace('{environment}', $this->environmentId, $uri);

        // Make the request
        $response = $this->client->request($method, $uri, $options);

        // Handle unsuccessful responses
        $status = $response->getStatusCode();

        // Get the response data
        try {
            $data = \GuzzleHttp\json_decode((string) $response->getBody(), true);
        } catch (\InvalidArgumentException $e) {
            $data = (string) $response->getBody();
            $this->logger->error(
                static::LOG_RESPONSE_INVALID_JSON,
                \array_merge($logContext, ['response_body' => $data])
            );
        }

        if ($status < 200 || $status > 299) {
            // Log the error and throw an exception
            $this->logger->error(
                static::LOG_RESPONSE_ERROR,
                \array_merge($logContext, ['status_code' => $status, 'response_body' => $data])
            );

            throw new FailedResponseException("HTTP status $status received.", $status);
        }

        if (\is_array($data)) {
            return $data;
        }

        $this->logger->error(self::LOG_RESPONSE_MISSING_DATA, \array_merge($logContext, ['data' => $data]));

        throw new InvalidResponseException('Cannot parse data as JSON.');
    }

    public function getCreditTypesForNetwork(Network $network): CreditTypeCollection
    {
        $path = (string) $network->getLink('credit_types');

        return $this->getCreditTypesForPath($path);
    }

    public function getCreditTypesForNetworkId(string $networkId): CreditTypeCollection
    {
        $path = \str_replace('{network}', $networkId, self::PATH_GET_CREDIT_TYPES_FOR_NETWORK);

        return $this->getCreditTypesForPath($path);
    }

    /**
     * @throws FailedResponseException
     * @throws InvalidResponseException
     * @throws \Throwable & GuzzleException
     */
    private function getCreditTypesForPath(string $path): CreditTypeCollection
    {
        // Log the request
        $logContext = $this->createLogContext();
        $this->logger->info(self::LOG_REQUEST_GET_CREDIT_TYPES_FOR_NETWORK, $logContext);

        // Make the API call
        $data = $this->makeRequest('GET', $path, [], $logContext);

        // Log a valid response
        $this->logger->info(self::LOG_RESPONSE_OK, $logContext);

        return new CreditTypeCollection($data);
    }

    public function createCredit(
        string $msisdn,
        $network,
        string $subscriptionType,
        Money $value,
        bool $sendSmsConfirmation,
        ?string $clientReference = null
    ): Credit {
        if ($network instanceof Network) {
            $networkId = $network->getId();
        } else {
            $networkId = (string) $network;
        }
        // Log the request
        $logContext = $this->createLogContext();
        $this->logger->info(static::LOG_REQUEST_CREATE_CREDIT, $logContext);

        // Make the API call
        $data = $this->makeRequest(
            'POST',
            static::PATH_CREATE_CREDIT,
            [
                'json' => [
                    'msisdn' => $msisdn,
                    'network' => $networkId,
                    'send_sms_confirmation' => $sendSmsConfirmation,
                    'subscription_type' => $subscriptionType,
                    'type' => 'BALANCE',
                    'credit_value' => MoneyConverter::moneyToArray($value),
                    'client_reference' => $clientReference,
                ],
            ],
            $logContext
        );

        // Log a valid response
        $this->logger->info(static::LOG_RESPONSE_OK, $logContext);

        return Credit::fromJsonArray($data);
    }

    public function getCredits(): CreditCollection
    {
        $logContext = $this->createLogContext();
        $this->logger->info(static::LOG_REQUEST_GET_CREDITS, $logContext);
        $data = $this->makeRequest('POST', static::PATH_CREATE_CREDIT);

        return new CreditCollection($data, $this);
    }

    public function getCredit(string $id): Credit
    {
        // Log the request
        $logContext = [
            'request_id' => \uniqid('', true),
            'id' => $id,
        ];
        $this->logger->info(static::LOG_REQUEST_GET_CREDIT, $logContext);

        // Make the API call
        $path = \str_replace('{id}', $id, static::PATH_GET_CREDIT);
        $data = $this->makeRequest('GET', $path, [], $logContext);

        // Log a valid response
        $this->logger->info(static::LOG_RESPONSE_OK, $logContext);

        return Credit::fromJsonArray($data);
    }

    public function getPageByRel(string $rel, PaginatedCollection $collection): ?PaginatedCollection
    {
        $link = $collection->getLink($rel);

        if (null === $link) {
            return null;
        }

        // Log the request
        $logContext = $this->createLogContext();
        $this->logger->info((string) $link, $logContext);

        $data = $this->makeRequest('GET', (string) $link, [], $logContext);

        /** @var class-string<PaginatedCollection> */
        $className = \get_class($collection);

        return new $className($data, $this);
    }

    public function getRefreshed(HateoasInterface $resource): HateoasInterface
    {
        if (null === $self = $resource->getLink('self')) {
            throw new UnrefreshableException('Cannot find URL for this object');
        }
        $data = $this->makeRequest('GET', (string) $self);

        return $resource::fromJsonArray($data);
    }

    public function getEligibilityForNetworkIds(string $msisdn, array $networkIds): array
    {
        $networks = \implode(',', $networkIds);
        $uri = \strtr(static::PATH_GET_ELIGIBILITY, ['{msisdn}' => $msisdn]);
        $data = $this->makeRequest('GET', $uri, [
            'query' => ['networks' => $networks],
        ]);

        return \array_map(static function (array $item): Eligibility {
            return Eligibility::fromJsonArray($item);
        }, $data['_embedded']['eligibility']);
    }

    public function getEligibilityForNetworks(string $msisdn, iterable $networks): array
    {
        $networks = (static function (Network ...$networks): array {
            return $networks;
        })(...$networks);

        $networkIds = \array_map(static function (Network $network): string {
            return $network->getId();
        }, $networks);

        return $this->getEligibilityForNetworkIds($msisdn, $networkIds);
    }
}
