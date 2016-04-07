<?php

namespace OroB2B\Bundle\TaxBundle\Transformer;

use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\Taxable;

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
     * @param Result $result
     * @param Taxable $taxable
     * @return TaxValue
     */
    public function reverseTransform(Result $result, Taxable $taxable);
}
