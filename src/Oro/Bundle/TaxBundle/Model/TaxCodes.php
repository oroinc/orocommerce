<?php

namespace Oro\Bundle\TaxBundle\Model;

/**
 * Represents a collection of tax codes.
 */
class TaxCodes
{
    /** @var TaxCode[] */
    private array $codes;
    private ?array $typedTaxCodes = null;
    private ?bool $fullFilledTaxCode = null;

    /**
     * @param TaxCode[] $codes
     */
    public function __construct(array $codes = [])
    {
        $this->codes = [];
        foreach ($codes as $code) {
            if ($code->getCode()) {
                $this->codes[] = $code;
            }
        }
    }

    /**
     * @param TaxCode[] $codes
     *
     * @return TaxCodes
     */
    public static function create(array $codes = []): TaxCodes
    {
        return new static($codes);
    }

    /**
     * @return TaxCode[]
     */
    public function getCodes(): array
    {
        return $this->codes;
    }

    /**
     * @return string[] [type => code, ...]
     */
    public function getPlainTypedCodes(): array
    {
        if (null === $this->typedTaxCodes) {
            $this->typedTaxCodes = [];
            foreach ($this->codes as $code) {
                $this->typedTaxCodes[$code->getType()][] = $code->getCode();
            }
        }

        return $this->typedTaxCodes;
    }

    /**
     * @return string[]
     */
    public function getAvailableTypes(): array
    {
        return [TaxCodeInterface::TYPE_PRODUCT, TaxCodeInterface::TYPE_ACCOUNT];
    }

    public function getHash(): string
    {
        return md5(json_encode($this->getPlainTypedCodes(), JSON_THROW_ON_ERROR));
    }

    public function isFullFilledTaxCode(): bool
    {
        if (null === $this->fullFilledTaxCode) {
            $this->fullFilledTaxCode = true;
            $plainTypeCodes = $this->getPlainTypedCodes();
            $availableTypes = $this->getAvailableTypes();
            foreach ($availableTypes as $availableType) {
                if (!\array_key_exists($availableType, $plainTypeCodes)) {
                    $this->fullFilledTaxCode = false;
                    break;
                }
            }
        }

        return $this->fullFilledTaxCode;
    }
}
