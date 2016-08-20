<?php

namespace Oro\Bundle\SaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class QuoteProduct extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.sale.quoteproduct.product.blank';

    /**
     * @var string
     */
    public $service = 'orob2b_sale.validator.quote_product';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return $this->service;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return [self::CLASS_CONSTRAINT];
    }
}
