<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2018
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @author Rick Ogden <rick@airtimerewards.com>
 */
class ClientTest extends TestCase
{
    /**
     * @var \GuzzleHttp\Client
     */
    private $guzzleClient;
    /**
     * @var Client
     */
    private $client;

    /**
     * @var MockHandler
     */
    private $mock;

    /**
     * @var array
     */
    private $history;

    protected function setUp(): void
    {
        $this->history = [];
        $history = Middleware::history($this->history);
        $this->mock = new MockHandler();
        $handler = HandlerStack::create($this->mock);
        $handler->push($history);
        $this->guzzleClient = new \GuzzleHttp\Client(['handler' => $handler]);
        $this->client = new Client($this->guzzleClient, 'foo', 'foo', new NullLogger());
    }

    public function testRequestHeaders(): void
    {
        $this->appendResponse(\file_get_contents(__DIR__.'/../src/Test/data/all_networks.json'));
        $this->client->getNetworks();
        $request = $this->getLastRequest();
        $this->assertContains('Bearer foo', $request->getHeader('Authorization'));
    }

    public function testGetNetworks(): void
    {
        $this->appendResponse(\file_get_contents(__DIR__.'/../src/Test/data/all_networks.json'));
        $this->appendResponse(\file_get_contents(__DIR__.'/../src/Test/data/filtered_networks.json'));
        $allNetworks = $this->client->getNetworks();
        $this->assertSame('/v1/networks', (string) $this->getLastRequest()->getUri());
        $network = $allNetworks->current();
        $this->assertSame('3', $network->getBrand());
        $this->assertSame('96ef03d9-ae5e-4ee6-96aa-9dd207274b77', $network->getId());
        $this->assertSame('/v1/networks/96ef03d9-ae5e-4ee6-96aa-9dd207274b77', (string) $network->getLink('self'));
        $this->assertSame('/v1/networks/96ef03d9-ae5e-4ee6-96aa-9dd207274b77/credit-types', (string) $network->getLink('credit_types'));
        $this->assertCount(43, $allNetworks);
        $filteredNetworks = $this->client->getNetworks('447990099876');
        $this->assertSame('/v1/networks?msisdn=447990099876', (string) $this->getLastRequest()->getUri());
        $this->assertCount(1, $filteredNetworks);
        $network = $filteredNetworks->current();
        $this->assertSame('O2', $network->getBrand());
        $this->assertSame('6efa2e06-3607-46c5-a722-6da971295edf', $network->getId());
    }

    public function testGetCredits(): void
    {
        $this->appendResponse(\file_get_contents(__DIR__.'/../src/Test/data/credit_collection.json'));
        $this->appendResponse(\file_get_contents(__DIR__.'/../src/Test/data/credit_collection.json'));
        $credits = $this->client->getCredits();
        $this->assertCount(5, $credits);
        $this->assertSame(5, $credits->getTotal());
        $this->assertSame(1, $credits->getPages());
        $this->assertSame(1, $credits->getPageNumber());

        /** @var CreditCollection $credits2 */
        $credits2 = $credits->getFirstPage();
        $this->assertCount(5, $credits2);
        $request = $this->getLastRequest();
        $this->assertSame('/v1/environments/8a9ab413-94fc-48a1-9889-59b94ca397bf/credits?page=1&limit=50', (string) $request->getUri());
    }

    public function testGetCreditTypesForNetwork(): void
    {
        $this->appendResponse(\file_get_contents(__DIR__.'/../src/Test/data/credit_types.json'));
        $this->appendResponse(\file_get_contents(__DIR__.'/../src/Test/data/credit_types.json'));
        $data = $this->client->getCreditTypesForNetworkId('foo');
        $this->assertSame('/v1/networks/foo/credit-types', (string) $this->getLastRequest()->getUri());
        $this->assertCount(2, $data);
        $creditType = $data->current();
        $this->assertSame('8958d017-1f5c-493a-af5b-51f97a398268', $creditType->getId());
        $this->assertSame('BALANCE', $creditType->getType());
        $this->assertTrue(Money::GBP(1)->equals($creditType->getValueMinimum()));
        $this->assertTrue(Money::GBP(3000)->equals($creditType->getValueMaximum()));
        $this->assertTrue(Money::GBP(1)->equals($creditType->getValueIncrement()));
        $jsonData = \GuzzleHttp\json_decode(
            \file_get_contents(__DIR__.'/../src/Test/data/filtered_networks.json'),
            true
        );
        $network = Network::fromJsonArray($jsonData['_embedded']['networks'][0]);
        $this->client->getCreditTypesForNetwork($network);
        $this->assertSame((string) $network->getLink('credit_types'), (string) $this->getLastRequest()->getUri());
    }

    public function testCreateCredit(): void
    {
        $jsonRequestBody = <<<'EOT'
{
	"msisdn":"447987654322",
	"type":"BALANCE",
	"credit_value":{"amount":1000,"currency":"GBP"},
	"client_reference":"test-reference",
	"network":"15532f47-7834-4da6-b887-d22de12aadfb",
	"send_sms_confirmation":false,
	"subscription_type":"POSTPAID"
}
EOT;
        $this->appendResponse(\file_get_contents(__DIR__.'/../src/Test/data/credit.json'));
        $this->client->createCredit(
            '447987654322',
            '15532f47-7834-4da6-b887-d22de12aadfb',
            'POSTPAID',
            Money::GBP(1000),
            false,
            'test-reference'
        );
        $this->assertSame('/v1/environments/foo/credits', (string) $this->getLastRequest()->getUri());
        $this->assertContains('application/json', $this->getLastRequest()->getHeader('Content-Type'));
        $this->assertJsonStringEqualsJsonString($jsonRequestBody, (string) $this->getLastRequest()->getBody());
    }

    public function testGetCredit(): void
    {
        $this->appendResponse(\file_get_contents(__DIR__.'/../src/Test/data/credit.json'));
        $this->appendResponse(\file_get_contents(__DIR__.'/../src/Test/data/credit.json'));
        $credit = $this->client->getCredit('id');
        $this->assertSame('/v1/credits/id', (string) $this->getLastRequest()->getUri());
        $this->assertSame('8b617efa-de76-483d-bf99-9e4de0ce54e3', $credit->getId());
        $this->assertSame('PENDING', $credit->getStatus());
        $this->assertSame('ref5', $credit->getClientReference());
        /** @var Credit $credit2 */
        $credit2 = $this->client->getRefreshed($credit);
        $this->assertInstanceOf(Credit::class, $credit2);
        $this->assertSame('8b617efa-de76-483d-bf99-9e4de0ce54e3', $credit2->getId());
        $this->assertSame('PENDING', $credit2->getStatus());
        $this->assertSame('ref5', $credit2->getClientReference());
    }

    public function testGetEligibility(): void
    {
        $this->appendResponse(\file_get_contents(__DIR__.'/../src/Test/data/eligibility.json'));
        $eligibility = $this->client->getEligibilityForNetworkIds('447990099876', ['network1', 'network2']);
        $this->assertSame(
            '/v1/environments/foo/eligibility/447990099876?networks=network1%2Cnetwork2',
            (string) $this->getLastRequest()->getUri()
        );

        $this->assertCount(2, $eligibility);
        $this->assertInstanceOf(Eligibility::class, $eligibility[0]);
        $this->assertSame('UNKNOWN', $eligibility[0]->getEligible());
        $this->assertSame('O2', $eligibility[0]->getNetwork()->getBrand());
        $this->assertSame('ELIGIBLE', $eligibility[1]->getEligible());
        $this->assertSame('EE', $eligibility[0]->getNetwork()->getBrand());
    }

    protected function appendResponse(string $data, int $responseCode = 200): void
    {
        $this->mock->append(new Response($responseCode, ['Content-Type' => 'application/json'], $data));
    }

    protected function getLastRequest(): Request
    {
        return \end($this->history)['request'];
    }

    protected function getLastResponse(): Response
    {
        return \end($this->history)['response'];
    }
}
