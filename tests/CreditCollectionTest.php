<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2018
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @author Rick Ogden <rick@airtimerewards.com>
 */
class CreditCollectionTest extends TestCase
{
    /**
     * @var CreditCollection
     */
    private $creditCollection;

    protected function setUp()
    {
        $client = Client::createClient('test', 'test', new NullLogger());
        $data = \GuzzleHttp\json_decode(\file_get_contents(__DIR__.'/../src/Test/data/credit_collection.json'), true);
        $this->creditCollection = new CreditCollection($data, $client);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(CreditCollection::class, $this->creditCollection);
        $this->assertCount(5, $this->creditCollection);
        foreach ($this->creditCollection as $credit) {
            $this->assertInstanceOf(Credit::class, $credit);
            $this->assertSame(12, \mb_strlen($credit->getMsisdn()));
        }

        $this->assertSame(
            '/v1/environments/8a9ab413-94fc-48a1-9889-59b94ca397bf/credits?page=1&limit=50',
            (string) $this->creditCollection->getLink('first')
        );
        $this->assertSame(
            '/v1/environments/8a9ab413-94fc-48a1-9889-59b94ca397bf/credits?page=1&limit=50',
            (string) $this->creditCollection->getLink('last')
        );

        $this->assertNull($this->creditCollection->getLink('next'));
    }
}
