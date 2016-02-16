<?php

namespace OroB2B\Bundle\TaxBundle\Model;

class TaxCodes
{
    /** @var \ArrayObject|TaxCode[] */
    protected $codes;

    /**
     * @param TaxCode[]|string[] $codes
     */
    public function __construct(array $codes = [])
    {
        $this->codes = new \ArrayObject();

        foreach ($codes as $code) {
            $this->addCode($code);
        }
    }

    /**
     * @param TaxCode[]|string[] $codes
     * @return TaxCodes
     */
    public static function create(array $codes = [])
    {
        return new static($codes);
    }

    /**
     * @param TaxCode $code
     */
    public function addCode(TaxCode $code)
    {
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
}
