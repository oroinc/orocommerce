<?php

namespace OroB2B\Bundle\SaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class AllowedQuoteDemandQuantity extends Constraint
{
    /**
     * @var string
     */
    public $notEqualQuantityMessage = 'orob2b.sale.quoteproductoffer.configurable.quantity.equal';

    /**
     * @var string
     */
    public $lessQuantityMessage = 'orob2b.sale.quoteproductoffer.configurable.quantity.less';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
