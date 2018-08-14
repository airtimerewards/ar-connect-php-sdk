<?php

/**
 * This file is part of the AR Connect SDK.
 *
 * © Airtime Rewards 2018
 */

declare(strict_types=1);

namespace AirtimeRewards\ARConnect\Hateoas;

use AirtimeRewards\ARConnect\Hateoas\Traits\HateoasLinksTrait;

/**
 * @author Rick Ogden <rick@airtimerewards.com>
 */
abstract class HateoasCollection implements \SeekableIterator, \Countable, HateoasInterface
{
    use HateoasLinksTrait;

    protected $position = 0;

    /**
     * {@inheritdoc}
     *
     * @return HateoasCollection
     */
    public static function fromJsonArray(array $data): self
    {
        return new static($data);
    }

    public function __construct(array $data)
    {
        if (isset($data['_links'])) {
            $this->populateLinks($data['_links']);
        }

        if (isset($data['_embedded'])) {
            $this->populateEmbedded($data['_embedded']);
        }
    }

    /**
     * @throws \InvalidArgumentException if the embedded data is not the data that is expected
     */
    abstract protected function populateEmbedded(array $embeddedData): void;

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function seek($position): void
    {
        $oldPosition = $this->position;
        $this->position = $position;

        if (!$this->valid()) {
            $this->position = $oldPosition;

            throw new \OutOfBoundsException(\sprintf('Invalid seek position (%d)', $position));
        }
    }
}
