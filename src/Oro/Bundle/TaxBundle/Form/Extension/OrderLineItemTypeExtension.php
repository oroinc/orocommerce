<?php

namespace Oro\Bundle\TaxBundle\Form\Extension;

use Oro\Bundle\OrderBundle\Form\Section\SectionProvider;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

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

    public function __construct(
        TaxationSettingsProvider $taxationSettingsProvider,
        TaxProviderRegistry $taxProviderRegistry,
        TotalProcessorProvider $totalProcessorProvider,
        SectionProvider $sectionProvider
    ) {
        $this->taxationSettingsProvider = $taxationSettingsProvider;
        $this->taxProviderRegistry = $taxProviderRegistry;
        $this->totalProcessorProvider = $totalProcessorProvider;
        $this->sectionProvider = $sectionProvider;
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [OrderLineItemType::class];
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

        foreach (self::getExtendedTypes() as $extendedType) {
            $this->sectionProvider->addSections($extendedType, $sections);
        }
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
