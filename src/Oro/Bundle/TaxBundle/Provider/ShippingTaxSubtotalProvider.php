<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\TaxBundle\Model\Result;

/**
 * Subtotal provider for shipping tax.
 */
class ShippingTaxSubtotalProvider extends AbstractTaxSubtotalProvider
{
    public const SUBTOTAL_ORDER = 420;

    protected function createSubtotal(): Subtotal
    {
        $subtotal = new Subtotal();

        $subtotal->setType(self::TYPE);
        $label = 'oro.tax.subtotals.shipping_' . self::TYPE;
        $subtotal->setLabel($this->translator->trans($label));
        $subtotal->setVisible(false);
        $subtotal->setSortOrder(self::SUBTOTAL_ORDER);
        $subtotal->setRemovable(true);

        return $subtotal;
    }

    protected function fillSubtotal(Subtotal $subtotal, Result $tax): Subtotal
    {
        $subtotal->setAmount($tax->getShipping()->getTaxAmount());
        $subtotal->setCurrency($tax->getShipping()->getCurrency());
        $subtotal->setVisible(false);

        if ($this->taxationSettingsProvider->isShippingRatesIncludeTax()) {
            $subtotal->setOperation(Subtotal::OPERATION_IGNORE);
        }

        $subtotal->setData($tax->getArrayCopy());

        return $subtotal;
    }
}
