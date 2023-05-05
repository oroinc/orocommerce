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

    /**
     * {@inheritdoc}
     */
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

    protected function fillSubtotal(Subtotal $subtotal, Result $tax): Subtotal
    {
        $itemTotalAmount = 0.0;
        $currency = "";
        foreach ($tax->getItems() as $item) {
            $itemTotalAmount += (float)$item->getRow()->getTaxAmount();
            $currency = $item->getRow()->getCurrency();
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
