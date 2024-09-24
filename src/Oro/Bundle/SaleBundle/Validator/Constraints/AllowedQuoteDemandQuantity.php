<?php

namespace Oro\Bundle\SaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class AllowedQuoteDemandQuantity extends Constraint
{
    /**
     * @var string
     */
    public $notEqualQuantityMessage = 'oro.sale.quoteproductoffer.configurable.quantity.equal';

    /**
     * @var string
     */
    public $lessQuantityMessage = 'oro.sale.quoteproductoffer.configurable.quantity.less';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
