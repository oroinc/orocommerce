<?php

namespace Oro\Bundle\TaxBundle\Model;

/**
 * Represents a tax code.
 */
class TaxCode implements TaxCodeInterface
{
    private string $code;
    private string $type;

    public function __construct(string $code, string $type)
    {
        $this->code = $code;
        $this->type = $type;
    }

    public static function create(string $code, string $type): TaxCode
    {
        return new static($code, $type);
    }

    /**
     * {@inheritDoc}
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return $this->type;
    }
}
