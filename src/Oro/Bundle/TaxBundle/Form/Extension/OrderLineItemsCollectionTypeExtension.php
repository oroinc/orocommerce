<?php

namespace Oro\Bundle\TaxBundle\Form\Extension;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemsCollectionType;
use Oro\Bundle\TaxBundle\Manager\TaxValueManager;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Extends the order line items form to preload and display tax values for line items.
 */
class OrderLineItemsCollectionTypeExtension extends AbstractTypeExtension
{
    /**
     * @var TaxationSettingsProvider
     */
    private $taxationSettingsProvider;

    /**
     * @var TaxValueManager
     */
    private $taxValueManager;

    public function __construct(
        TaxationSettingsProvider $taxationSettingsProvider,
        TaxValueManager $taxValueManager
    ) {
        $this->taxationSettingsProvider = $taxationSettingsProvider;
        $this->taxValueManager = $taxValueManager;
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [OrderLineItemsCollectionType::class];
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (!$this->taxationSettingsProvider->isEnabled()) {
            return;
        }

        $ids = [];
        /** @var OrderLineItem $lineItem */
        foreach ($form->getData() as $lineItem) {
            $ids[] = $lineItem->getId();
        }

        $this->taxValueManager->preloadTaxValues(OrderLineItem::class, $ids);
    }
}
