<?php

namespace Oro\Bundle\SaleBundle\Quote\Calculable\ParameterBag;

use Oro\Bundle\SaleBundle\Quote\Calculable\CalculableQuoteInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class ParameterBagCalculableQuote extends ParameterBag implements CalculableQuoteInterface
{
    const FIELD_LINE_ITEMS = 'line_items';

    /**
     * {@inheritdoc}
     */
    public function getLineItems()
    {
        return $this->get(self::FIELD_LINE_ITEMS);
    }
}
