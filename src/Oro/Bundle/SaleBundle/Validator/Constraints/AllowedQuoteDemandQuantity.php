<?php

namespace Oro\Bundle\SaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether an offer quantity for a quote product demand is allowed.
 */
class AllowedQuoteDemandQuantity extends Constraint
{
    public string $notEqualQuantityMessage = 'oro.sale.quoteproductoffer.configurable.quantity.equal';
    public string $lessQuantityMessage = 'oro.sale.quoteproductoffer.configurable.quantity.less';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
