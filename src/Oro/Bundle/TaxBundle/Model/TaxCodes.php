<?php

namespace Oro\Bundle\TaxBundle\Model;

class TaxCodes
{
    /** @var \ArrayObject|TaxCode[] */
    protected $codes;

    /**
     * @param TaxCode[] $codes
     */
    public function __construct(array $codes = [])
    {
        $this->codes = new \ArrayObject();

        foreach ($codes as $code) {
            $this->addCode($code);
        }
    }

    /**
     * @param TaxCode[] $codes
     * @return TaxCodes
     */
    public static function create(array $codes = [])
    {
        return new static($codes);
    }

    /**
     * @param TaxCode $code
     */
    public function addCode($code)
    {
        if (!$code instanceof TaxCode) {
            return;
        }

        if (!$code->getCode()) {
            return;
        }

        $this->codes->append($code);
    }

    /**
     * @return TaxCode[]
     */
    public function getCodes()
    {
        return $this->codes->getArrayCopy();
    }

    /**
     * @return array
     */
    public function getPlainTypedCodes()
    {
        $typedTaxCodes = [];

        foreach ($this->getCodes() as $code) {
            $typedTaxCodes[$code->getType()][] = $code->getCode();
        }

        return $typedTaxCodes;
    }

    /**
     * @return array
     */
    public function getAvailableTypes()
    {
        return [TaxCodeInterface::TYPE_PRODUCT, TaxCodeInterface::TYPE_ACCOUNT];
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return md5(json_encode($this->getPlainTypedCodes()));
    }

    /** @return bool */
    public function isFullFilledTaxCode()
    {
        $plainTypeCodes = $this->getPlainTypedCodes();
        foreach ($this->getAvailableTypes() as $availableType) {
            if (!array_key_exists($availableType, $plainTypeCodes)) {
                return false;
            }
        }

        return true;
    }
}
