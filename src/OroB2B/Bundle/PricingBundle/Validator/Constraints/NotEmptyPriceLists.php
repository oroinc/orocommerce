<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\NotBlank;

class NotEmptyPriceLists extends NotBlank
{
    /**
     * @var string
     */
    public $message = 'orob2b.pricing.validators.price_list.not_empty_price_lists.message';
}
