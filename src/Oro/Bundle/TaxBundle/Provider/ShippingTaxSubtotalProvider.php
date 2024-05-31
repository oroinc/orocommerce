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

    public function getSubtotal($entity)
    {
        $subTotal = parent::getSubtotal($entity);
        $this->fillOperation($subTotal, $entity);

        return $subTotal;
    }

    public function getCachedSubtotal($entity)
    {
        $subTotal = parent::getCachedSubtotal($entity);
        $this->fillOperation($subTotal, $entity);

        return $subTotal;
    }

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
        $subtotal->setData($tax->getArrayCopy());

        return $subtotal;
    }

    private function fillOperation(Subtotal $subtotal, ?object $entity = null): void
    {
        if ($this->taxationSettingsProvider->isShippingRatesIncludeTaxWithEntity($entity)) {
            $subtotal->setOperation(Subtotal::OPERATION_IGNORE);
        }
    }
}
