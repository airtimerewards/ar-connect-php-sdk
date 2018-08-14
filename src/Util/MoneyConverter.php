<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2018
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect\Util;

use Money\Currency;
use Money\Money;

/**
 * @author Rick Ogden <rick@airtimerewards.com>
 */
class MoneyConverter
{
    /**
     * Converts an array of money data to a Money object.
     *
     * @param array $data An array containing the keys "amount" and "currency". E.g. `['amount' => 200, 'currency' => 'GBP']` is £2.
     */
    public static function arrayToMoney(array $data): Money
    {
        return new Money($data['amount'], new Currency($data['currency']));
    }

    /**
     * @return array an array containing the keys "amount" (integer) and "currency" (string)
     */
    public static function moneyToArray(Money $money): array
    {
        return ['amount' => (int) $money->getAmount(), 'currency' => $money->getCurrency()->getCode()];
    }
}
