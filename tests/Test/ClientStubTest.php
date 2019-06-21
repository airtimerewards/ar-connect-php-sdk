<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2019
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect\Test;

use AirtimeRewards\ARConnect\Credit;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ClientStubTest extends TestCase
{
    /**
     * @var ClientStub
     */
    protected $client;

    /**
     * @var \ReflectionMethod
     */
    protected $makeRequest;

    protected function setUp(): void/* The :void return type declaration that should be here would cause a BC issue */
    {
        $this->client = ClientStub::createClient('foo', 'foo', new NullLogger());
        $this->makeRequest = new \ReflectionMethod(ClientStub::class, 'makeRequest');
        $this->makeRequest->setAccessible(true);
    }

    public function testGetCredit(): void
    {
        $credit = $this->client->getCredit('foo');
        $this->assertTrue(Money::GBP(1000)->equals($credit->getCreditValue()));
        $this->assertSame('8b617efa-de76-483d-bf99-9e4de0ce54e3', $credit->getId());
        $this->assertSame('447987654322', $credit->getMsisdn());
        $this->assertSame('15532f47-7834-4da6-b887-d22de12aadfb', $credit->getNetwork());
        $this->assertTrue($credit->isPinBased());
        $this->assertSame('1234', $credit->getPinCode());
        $this->assertSame('03300696969', $credit->getPinIvr());
        $this->assertTrue($credit->getSendSmsConfirmation());
        $this->assertFalse($credit->getSmsConfirmationSent());
        $this->assertSame(Credit::STATUS_PENDING, $credit->getStatus());
        $this->assertSame(Credit::SUBSCRIPTION_TYPE_POSTPAID, $credit->getSubscriptionType());
    }

    public function testMakeRequest(): void
    {
        $data = $this->makeRequest('GET', '/v1/networks');
        $this->assertCount(43, $data['_embedded']['networks']);
        $data = $this->makeRequest('GET', '/v1/networks', ['query' => ['msisdn' => '447823221']]);
        $this->assertCount(1, $data['_embedded']['networks']);
        $data = $this->makeRequest('GET', '/v1/networks/networkId/credit-types');
        $this->assertCount(2, $data['_embedded']['credit_types']);
        $data = $this->makeRequest('GET', '/v1/quote');
        $this->assertArrayHasKey('pre_tax', $data);
        $data = $this->makeRequest('GET', '/v1/environments/{environment}/credits');
        $this->assertCount(5, $data['_embedded']['credits']);
    }

    protected function makeRequest(
        string $method,
        string $uri,
        array $options = [],
        array $logContext = []
    ): array {
        return $this->makeRequest->invokeArgs($this->client, [$method, $uri, $options, $logContext]);
    }
}
