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

/**
 * @coversDefaultClass \AirtimeRewards\ARConnect\Test\ARConnectClientStub
 */
final class ARConnectClientStubTest extends TestCase
{
    /** @var ARConnectClientStub */
    protected $client;

    protected function setUp(): void
    {
        $this->client = new ARConnectClientStub();
    }

    /**
     * @covers ::getCredit
     */
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
}
