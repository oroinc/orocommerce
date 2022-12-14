<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\TaxBundle\Model\Result;

/**
 * Subtotal provider for taxes. Here to only save a number to show up. Taxes used to calculate total please see
 * ShippingTaxSubtotalProvider and LineItemTaxSubtotalProvider.
 */
class TaxSubtotalProvider extends AbstractTaxSubtotalProvider
{
    public const TYPE = 'tax';
    public const SUBTOTAL_ORDER = 500;

    protected function createSubtotal(): Subtotal
    {
        $subtotal = new Subtotal();

        $subtotal->setType(self::TYPE);
        $label = 'oro.tax.subtotals.' . self::TYPE;
        $subtotal->setLabel($this->translator->trans($label));
        $subtotal->setVisible(false);
        $subtotal->setSortOrder(self::SUBTOTAL_ORDER);
        $subtotal->setRemovable(false);

        return $subtotal;
    }

    protected function fillSubtotal(Subtotal $subtotal, Result $tax): Subtotal
    {
        $subtotal->setAmount($tax->getTotal()->getTaxAmount());
        $subtotal->setCurrency($tax->getTotal()->getCurrency());
        $subtotal->setVisible((bool)$tax->getTotal()->getTaxAmount());
        $subtotal->setOperation(Subtotal::OPERATION_IGNORE); // Only for show up, always ignore.
        $subtotal->setData($tax->getArrayCopy());

        return $subtotal;
    }
}
