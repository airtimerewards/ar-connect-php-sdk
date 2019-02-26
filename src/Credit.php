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
 * AR Connect credit instance.
 *
 * @author Jaik Dean <jaik@airtimerewards.com>
 */
final class Credit implements HateoasInterface
{
    use HateoasLinksTrait;

    public const STATUS_CANCELED = 'CANCELED';
    public const STATUS_COMPLETE = 'COMPLETE';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_PENDING = 'PENDING';

    public const SUBSCRIPTION_TYPE_POSTPAID = 'POSTPAID';
    public const SUBSCRIPTION_TYPE_PREPAID = 'PREPAID';

    /**
     * @var string UUID string, for example '123e4567-e89b-12d3-a456-426655440000'
     */
    private $id;

    /**
     * @var string MSISDN (mobile number)
     */
    private $msisdn;

    /**
     * @var string UUID string, for example '123e4567-e89b-12d3-a456-426655440000'
     */
    private $network;

    /**
     * @var string Subscription type, one of the ::SUBSCRIPTION_TYPE_* constants
     */
    private $subscriptionType;

    /**
     * @var Money
     */
    private $creditValue;

    /**
     * @var bool whether the credit is PIN-based
     */
    private $pinBased;

    /**
     * @var string|null PIN code
     */
    private $pinCode;

    /**
     * @var string|null PIN IVR (the number to call to redeem the credit using the PIN code)
     */
    private $pinIvr;

    /**
     * @var bool whether a confirmation SMS should be sent to the MSISDN on success
     */
    private $sendSmsConfirmation;

    /**
     * @var bool whether a confirmation SMS has been sent to the MSISDN
     */
    private $smsConfirmationSent;

    /**
     * @var string one of the ::STATUS_* constants
     */
    private $status;

    /**
     * @var \DateTimeImmutable when the credit was first created
     */
    private $createdAt;
    /**
     * @var null|string
     */
    private $clientReference;

    /**
     * This creates a new instance of itself from a JSON decoded response.
     *
     * @param array $data decoded JSON in array format
     *
     * @return Credit
     */
    public static function fromJsonArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['msisdn'],
            $data['network'],
            $data['subscription_type'],
            MoneyConverter::arrayToMoney($data['credit_value']),
            $data['pin_based'],
            $data['pin_code'] ?? null,
            $data['pin_ivr'] ?? null,
            $data['send_sms_confirmation'],
            $data['sms_confirmation_sent'],
            $data['status'],
            new \DateTimeImmutable($data['created_at']),
            $data['client_reference'] ?? null,
            $data['_links'] ?? []
        );
    }

    public function __construct(
        string $id,
        string $msisdn,
        string $network,
        string $subscriptionType,
        Money $creditValue,
        bool $pinBased,
        ?string $pinCode,
        ?string $pinIvr,
        bool $sendSmsConfirmation,
        bool $smsConfirmationSent,
        string $status,
        \DateTimeImmutable $createdAt,
        ?string $clientReference = null,
        array $links = []
    ) {
        $this->id = $id;
        $this->msisdn = $msisdn;
        $this->network = $network;
        $this->creditValue = $creditValue;
        $this->pinBased = $pinBased;
        $this->pinCode = $pinCode;
        $this->pinIvr = $pinIvr;
        $this->sendSmsConfirmation = $sendSmsConfirmation;
        $this->smsConfirmationSent = $smsConfirmationSent;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->clientReference = $clientReference;
        $this->subscriptionType = $subscriptionType;
        $this->populateLinks($links);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getMsisdn(): string
    {
        return $this->msisdn;
    }

    public function getNetwork(): string
    {
        return $this->network;
    }

    public function getSubscriptionType(): string
    {
        return $this->subscriptionType;
    }

    public function getCreditValue(): Money
    {
        return $this->creditValue;
    }

    public function isPinBased(): bool
    {
        return $this->pinBased;
    }

    public function getPinCode(): ?string
    {
        return $this->pinCode;
    }

    public function getPinIvr(): ?string
    {
        return $this->pinIvr;
    }

    public function getSendSmsConfirmation(): bool
    {
        return $this->sendSmsConfirmation;
    }

    public function getSmsConfirmationSent(): bool
    {
        return $this->smsConfirmationSent;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getClientReference(): ?string
    {
        return $this->clientReference;
    }
}
