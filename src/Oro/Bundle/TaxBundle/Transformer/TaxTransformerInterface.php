<?php

namespace Oro\Bundle\TaxBundle\Transformer;

use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;

/**
 * Defines the contract for transforming between tax calculation results and persisted tax values.
 *
 * This interface provides bidirectional transformation between {@see Result} objects (which
 * represent calculated tax information) and {@see TaxValue} entities (which are persisted in the database).
 * Implementations handle the conversion of tax calculation results into a format suitable for storage and retrieval,
 * enabling tax values to be cached and reused without recalculation.
 */
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
