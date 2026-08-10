<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\TaxBundle\Factory\TaxFactory;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Model\Result;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Subtotal provider for line items taxes.
 */
class LineItemTaxSubtotalProvider extends AbstractTaxSubtotalProvider
{
    public const NAME = 'line_item_tax';
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
    public function getSubtotalByResult(Result $tax, object $entity): Subtotal
    {
        // Line item tax items are not stored with the loaded tax result and must be loaded on demand;
        // the recalculated result already contains them, so the guard keeps both paths consistent.
        if ($entity instanceof Order && !$tax->offsetExists(Result::ITEMS)) {
            $this->loadTaxItems($tax, $entity);
        }

        return parent::getSubtotalByResult($tax, $entity);
    }

    #[\Override]
    protected function createSubtotal(): Subtotal
    {
        $subtotal = new Subtotal();

        $subtotal->setType(self::TYPE);
        $subtotal->setName(self::NAME);
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
        /**
         * ITEMS_TOTAL is rounded once on the aggregated sum; getTaxes() values are already rounded per item.
         */
        $itemsTotal = $tax->getItemsTotal();

        $subtotal->setAmount($itemsTotal->getTaxAmount());
        $subtotal->setCurrency($itemsTotal->getCurrency() ?? '');
        $subtotal->setVisible(false);

        if ($this->taxationSettingsProvider->isProductPricesIncludeTax()) {
            $subtotal->setOperation(Subtotal::OPERATION_IGNORE);
        }

        $subtotal->setData($tax->getArrayCopy());

        return $subtotal;
    }

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
