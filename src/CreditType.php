<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2018
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect;

use AirtimeRewards\ARConnect\Hateoas\HateoasInterface;
use AirtimeRewards\ARConnect\Hateoas\Traits\HateoasLinksTrait;
use AirtimeRewards\ARConnect\Util\MoneyConverter;
use Money\Money;

/**
 * AR Connect credit type instance.
 *
 * @author Jaik Dean <jaik@airtimerewards.com>
 */
final class CreditType implements HateoasInterface
{
    use HateoasLinksTrait;

    public const TYPE_BALANCE = 'BALANCE';

    /**
     * @var string UUID string, for example '123e4567-e89b-12d3-a456-426655440000'
     */
    private $id;

    /**
     * @var string human-readable description
     */
    private $description;

    /**
     * @var string one of the ::TYPE_* constants
     */
    private $type;

    /**
     * @var Money minimum value permitted
     */
    private $valueMinimum;

    /**
     * @var Money maximum value permitted
     */
    private $valueMaximum;

    /**
     * @var Money value increments permitted
     */
    private $valueIncrement;

    /**
     * {@inheritdoc}
     *
     * @return CreditType
     */
    public static function fromJsonArray(array $data): self
    {
        $minimum = MoneyConverter::arrayToMoney($data['value_minimum']);

        return new self(
            $data['id'],
            $data['description'],
            $data['type'],
            $minimum,
            MoneyConverter::arrayToMoney($data['value_maximum']),
            new Money($data['value_increment'], $minimum->getCurrency())
        );
    }

    public function __construct(
        string $id,
        string $description,
        string $type,
        Money $valueMinimum,
        Money $valueMaximum,
        Money $valueIncrement,
        array $links = []
    ) {
        $this->id = $id;
        $this->description = $description;
        $this->type = $type;
        $this->valueMinimum = $valueMinimum;
        $this->valueMaximum = $valueMaximum;
        $this->valueIncrement = $valueIncrement;
        $this->populateLinks($links);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValueMinimum(): Money
    {
        return $this->valueMinimum;
    }

    public function getValueMaximum(): Money
    {
        return $this->valueMaximum;
    }

    public function getValueIncrement(): Money
    {
        return $this->valueIncrement;
    }

    /**
     * Check if this credit type allows a particular value.
     */
    public function allowsValue(Money $value): bool
    {
        return $value->isSameCurrency($this->valueMinimum)
            && $value->greaterThanOrEqual($this->valueMinimum)
            && $value->lessThanOrEqual($this->valueMaximum)
            && 0 === ($value->subtract($this->valueMinimum)->getAmount() % $this->valueIncrement->getAmount());
    }
}
