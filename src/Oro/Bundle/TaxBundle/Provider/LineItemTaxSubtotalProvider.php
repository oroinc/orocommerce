<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Factory\TaxFactory;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Model\Result;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Subtotal provider for line items taxes.
 */
class LineItemTaxSubtotalProvider extends AbstractTaxSubtotalProvider
{
    public const SUBTOTAL_ORDER = 410;

    private TaxManager $taxManager;

    public function __construct(
        TranslatorInterface $translator,
        TaxProviderRegistry $taxProviderRegistry,
        TaxFactory $taxFactory,
        TaxationSettingsProvider $taxationSettingsProvider,
        TaxManager $taxManager
    ) {
        parent::__construct($translator, $taxProviderRegistry, $taxFactory, $taxationSettingsProvider);

        $this->taxManager = $taxManager;
    }

    #[\Override]
    public function getCachedSubtotal($entity): Subtotal
    {
        $subtotal = $this->createSubtotal();
        try {
            $tax = $this->getProvider()->loadTax($entity);

            if ($entity instanceof Order && !$tax->offsetExists(Result::ITEMS)) {
                $this->loadTaxItems($tax, $entity);
            }

            $this->fillSubtotal($subtotal, $tax);
        } catch (TaxationDisabledException $e) {
        }

        return $subtotal;
    }

    #[\Override]
    protected function createSubtotal(): Subtotal
    {
        $subtotal = new Subtotal();

        $subtotal->setType(self::TYPE);
        $label = 'oro.tax.subtotals.lineitem_' . self::TYPE;
        $subtotal->setLabel($this->translator->trans($label));
        $subtotal->setVisible(false);
        $subtotal->setSortOrder(self::SUBTOTAL_ORDER);
        $subtotal->setRemovable(true);

        return $subtotal;
    }

    #[\Override]
    protected function fillSubtotal(Subtotal $subtotal, Result $tax, ?object $entity = null): Subtotal
    {
        $itemTotalAmount = 0.0;
        $currency = "";
        foreach ($tax->getTaxes() as $taxElement) {
            $itemTotalAmount += (float)$taxElement->getTaxAmount();
            $currency = $taxElement->getCurrency();
        }

        $subtotal->setAmount($itemTotalAmount);
        $subtotal->setCurrency($currency);
        $subtotal->setVisible(false);

        if ($this->taxationSettingsProvider->isProductPricesIncludeTax()) {
            $subtotal->setOperation(Subtotal::OPERATION_IGNORE);
        }

        $subtotal->setData($tax->getArrayCopy());

        return $subtotal;
    }

    /**
     * @throws TaxationDisabledException
     */
    private function loadTaxItems($taxResult, $order): void
    {
        if ($order->getLineItems()) {
            $itemsResult = [];
            foreach ($order->getLineItems() as $lineItem) {
                $itemsResult[] = $this->taxManager->loadTax($lineItem);
            }
            if ($itemsResult) {
                $taxResult->offsetSet(Result::ITEMS, $itemsResult);
            }
        }
    }
}
