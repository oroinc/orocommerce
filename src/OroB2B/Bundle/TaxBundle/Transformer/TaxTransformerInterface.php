<?php

namespace OroB2B\Bundle\TaxBundle\Transformer;

use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Model\Result;

interface TaxTransformerInterface
{
    /**
     * Transform object to Result
     *
     * @param TaxValue $taxValue
     * @return Result
     */
    public function transform(TaxValue $taxValue);

    /**
     * Reverse transform Result to object
     *
     * @param TaxValue $taxValue
     * @param Result $result
     * @return TaxValue
     */
    public function reverseTransform(TaxValue $taxValue, Result $result);
}
