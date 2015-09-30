<?php

namespace OroB2B\Bundle\SaleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ConfigurableQuoteProductOffer extends Constraint
{
    /**
     * @var string
     */
    public $blankOfferMessage = 'orob2b.sale.quoteproductoffer.configurable.offer.blank';

    /**
     * @var string
     */
    public $notEqualQuantityMessage = 'orob2b.sale.quoteproductoffer.configurable.quantity.equal';

    /**
     * @var string
     */
    public $lessQuantityMessage = 'orob2b.sale.quoteproductoffer.configurable.quantity.less';
}
