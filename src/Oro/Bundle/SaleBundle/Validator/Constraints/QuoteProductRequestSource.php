<?php

namespace Oro\Bundle\SaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to validate that a quote product request is created from the same RFQ as the quote.
 */
class QuoteProductRequestSource extends Constraint
{
    public string $message = 'oro.sale.quoteproductrequest.request_product_item.invalid';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
