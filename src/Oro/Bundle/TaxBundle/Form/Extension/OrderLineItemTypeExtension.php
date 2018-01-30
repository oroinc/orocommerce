<?php

namespace Oro\Bundle\TaxBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Oro\Bundle\OrderBundle\Form\Section\SectionProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class OrderLineItemTypeExtension extends AbstractTypeExtension
{
    const BASE_ORDER = 50;

    /** @var TaxationSettingsProvider */
    protected $taxationSettingsProvider;

    /** @var TaxProviderRegistry */
    protected $taxProviderRegistry;

    /** @var TotalProcessorProvider */
    protected $totalProcessorProvider;

    /** @var SectionProvider */
    protected $sectionProvider;

    /** @var string */
    protected $extendedType;

    /**
     * @param TaxationSettingsProvider $taxationSettingsProvider
     * @param TaxProviderRegistry $taxProviderRegistry
     * @param TotalProcessorProvider $totalProcessorProvider
     * @param SectionProvider $sectionProvider
     * @param string $extendedType
     */
    public function __construct(
        TaxationSettingsProvider $taxationSettingsProvider,
        TaxProviderRegistry $taxProviderRegistry,
        TotalProcessorProvider $totalProcessorProvider,
        SectionProvider $sectionProvider,
        $extendedType
    ) {
        $this->taxationSettingsProvider = $taxationSettingsProvider;
        $this->taxProviderRegistry = $taxProviderRegistry;
        $this->totalProcessorProvider = $totalProcessorProvider;
        $this->sectionProvider = $sectionProvider;
        $this->extendedType = (string)$extendedType;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return $this->extendedType;
    }

    /** {@inheritdoc} */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (!$this->taxationSettingsProvider->isEnabled()) {
            return;
        }

        $sections = [];
        $sectionNames = [
            'taxes' => 'oro.tax.order_line_item.taxes.label',
        ];

        $order = self::BASE_ORDER;
        foreach ($sectionNames as $sectionName => $label) {
            $sections[$sectionName] = [
                'order' => $order++,
                'label' => $label,
            ];
        }

        $this->sectionProvider->addSections($this->getExtendedType(), $sections);
    }

    /** {@inheritdoc} */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!$this->taxationSettingsProvider->isEnabled()) {
            return;
        }

        $entity = $form->getData();
        if (!$entity) {
            return;
        }

        $view->vars['result'] = $this->getProvider()->getTax($entity);
    }


    /**
     * @return TaxProviderInterface
     */
    private function getProvider()
    {
        return $this->taxProviderRegistry->getEnabledProvider();
    }
}
