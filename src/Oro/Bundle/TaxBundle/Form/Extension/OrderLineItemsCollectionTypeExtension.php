<?php

namespace Oro\Bundle\TaxBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TaxBundle\Manager\TaxValueManager;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

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

    /**
     * @var string
     */
    private $extendedType;

    /**
     * @param TaxationSettingsProvider $taxationSettingsProvider
     * @param TaxValueManager $taxValueManager
     * @param string $extendedType
     */
    public function __construct(
        TaxationSettingsProvider $taxationSettingsProvider,
        TaxValueManager $taxValueManager,
        $extendedType
    ) {
        $this->taxationSettingsProvider = $taxationSettingsProvider;
        $this->taxValueManager = $taxValueManager;
        $this->extendedType = (string)$extendedType;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return $this->extendedType;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
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
