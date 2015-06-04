<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class PriceListCurrency extends Constraint
{
    /**
     * @var string
     */
    public $message = 'orob2b.pricing.validators.price_list.currency.message';

    /**
     * @var bool
     */
    public $useIntl = false;

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'orob2b_pricing_price_list_currency_validator';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'use_intl';
    }
}
