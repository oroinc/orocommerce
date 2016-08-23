<?php

namespace Oro\Bundle\TaxBundle\Transformer;

use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;

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
