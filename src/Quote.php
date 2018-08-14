<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2018
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect;

use Money\Money;

/**
 * @author Rick Ogden <rick@airtimerewards.com>
 */
class Quote
{
    /**
     * @var bool whether the client can afford to make the credit
     */
    private $canAfford;

    /**
     * @var Money the total price less tax
     */
    private $preTax;

    /**
     * @var Money the total tax
     */
    private $taxPaid;

    /**
     * @var Money the total price inclusive of tax
     */
    private $total;

    public function __construct(bool $canAfford, Money $preTax, Money $taxPaid, Money $total)
    {
        $this->canAfford = $canAfford;
        $this->preTax = $preTax;
        $this->taxPaid = $taxPaid;
        $this->total = $total;
    }

    public function canAfford(): bool
    {
        return $this->canAfford;
    }

    public function getPreTax(): Money
    {
        return $this->preTax;
    }

    public function getTaxPaid(): Money
    {
        return $this->taxPaid;
    }

    public function getTotal(): Money
    {
        return $this->total;
    }
}
