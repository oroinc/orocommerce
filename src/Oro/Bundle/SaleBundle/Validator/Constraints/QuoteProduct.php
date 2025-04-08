<?php

namespace Oro\Bundle\SaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether a quote product represents either a regular product or a free form product.
 */
class QuoteProduct extends Constraint
{
    public string $message = 'oro.sale.quoteproduct.product.blank';

    #[\Override]
    public function getTargets(): string|array
    {
        return [self::CLASS_CONSTRAINT];
    }
}
