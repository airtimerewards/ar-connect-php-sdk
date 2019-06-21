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
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Money\Money;
use Psr\Log\LoggerInterface;

/**
 * AR Connect API client.
 *
 * @author Jaik Dean <jaik@airtimerewards.com>
 * @author Rick Ogden <rick@airtimerewards.com>
 */
class Client
{
    protected const PATH_CREATE_CREDIT = '/v1/environments/{environment}/credits';
    protected const PATH_CREATE_QUOTE = '/v1/quote';
    protected const PATH_GET_CREDIT = '/v1/credits/{id}';
    protected const PATH_GET_ELIGIBILITY = '/v1/environments/{environment}/eligibility/{msisdn}';
    protected const PATH_GET_NETWORKS = '/v1/networks';
    protected const PATH_GET_CREDIT_TYPES_FOR_NETWORK = '/v1/networks/{network}/credit-types';

    protected const LOG_REQUEST_CREATE_CREDIT = '-> POST '.self::PATH_CREATE_CREDIT;
    protected const LOG_REQUEST_GET_CREDITS = '-> GET '.self::PATH_CREATE_CREDIT;
    protected const LOG_REQUEST_GET_CREDIT = '-> GET '.self::PATH_GET_CREDIT;
    protected const LOG_REQUEST_GET_CREDIT_TYPES_FOR_MSISDN = '-> GET '.self::PATH_GET_NETWORKS;
    protected const LOG_REQUEST_GET_CREDIT_TYPES_FOR_NETWORK = '-> GET '.self::PATH_GET_CREDIT_TYPES_FOR_NETWORK;
    protected const LOG_RESPONSE_ERROR = '<- Error response received from AR Connect';
    protected const LOG_RESPONSE_INVALID_JSON = '<- Invalid JSON received from AR Connect';
    protected const LOG_RESPONSE_MISSING_DATA = '<- Missing data received from AR Connect';
    protected const LOG_RESPONSE_OK = '<- Response OK';

    /**
     * @var string API URL
     */
    protected $endpoint;

    /**
     * @var string API token
     */
    protected $apiKey;

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $environmentId;

    /**
     * Intialise the Guzzle client.
     */
    public static function createClient(
        string $apiKey,
        string $environmentId,
        LoggerInterface $logger,
        string $endpoint = 'https://api.connect.airtimerewards.co.uk'
    ): self {
        $client = new GuzzleClient(['base_uri' => $endpoint]);

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

    /**
     * Get credit types available for a MSISDN (mobile number).
     *
     *
     * @param string $msisdn
     *
     * @throws FailedResponseException
     * @throws InvalidResponseException
     * @throws GuzzleException
     */
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

    protected function createLogContext(): array
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
     * @throws GuzzleException
     *
     * @return array Decoded JSON body
     */
    protected function makeRequest(
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

    /**
     * Get credit types available for a network.
     *
     * @param Network $network The network
     *
     * @throws FailedResponseException
     * @throws InvalidResponseException
     * @throws GuzzleException
     */
    public function getCreditTypesForNetwork(Network $network): CreditTypeCollection
    {
        $path = (string) $network->getLink('credit_types');

        return $this->getCreditTypesForPath($path);
    }

    /**
     * Get credit types available for a network ID.
     *
     *
     * @throws FailedResponseException
     * @throws InvalidResponseException
     * @throws GuzzleException
     */
    public function getCreditTypesForNetworkId(string $networkId): CreditTypeCollection
    {
        $path = \str_replace('{network}', $networkId, self::PATH_GET_CREDIT_TYPES_FOR_NETWORK);

        return $this->getCreditTypesForPath($path);
    }

    /**
     * @throws FailedResponseException
     * @throws InvalidResponseException
     * @throws GuzzleException
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

    /**
     * Send credit to a MSISDN (mobile number).
     *
     * @param Network|string $network Network object or Network ID
     *
     * @throws FailedResponseException
     * @throws InvalidResponseException
     * @throws GuzzleException
     */
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

    /**
     * Get a credit instance.
     *
     * @throws FailedResponseException
     * @throws InvalidResponseException
     * @throws GuzzleException
     */
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

    /**
     * @throws FailedResponseException
     * @throws GuzzleException
     * @throws InvalidResponseException
     */
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

        /**
         * @var class-string<PaginatedCollection>
         */
        $className = \get_class($collection);

        return new $className($data, $this);
    }

    /**
     * This will take any resource or collection and get the latest version of it.
     *
     * @throws FailedResponseException
     * @throws InvalidResponseException
     * @throws UnrefreshableException
     * @throws GuzzleException
     */
    public function getRefreshed(HateoasInterface $resource): HateoasInterface
    {
        if (null === $self = $resource->getLink('self')) {
            throw new UnrefreshableException('Cannot find URL for this object');
        }
        $data = $this->makeRequest('GET', (string) $self);

        return $resource::fromJsonArray($data);
    }

    /**
     * @param string[] $networkIds
     *
     * @throws GuzzleException
     * @throws InvalidResponseException
     * @throws FailedResponseException
     *
     * @return Eligibility[]
     */
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

    /**
     * @param iterable<Network> $networks
     *
     * @throws GuzzleException
     * @throws InvalidResponseException
     * @throws FailedResponseException
     *
     * @return Eligibility[]
     */
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
