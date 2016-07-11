<?php

namespace OroB2B\Bundle\TaxBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use OroB2B\Bundle\OrderBundle\Form\Section\SectionProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class OrderLineItemTypeExtension extends AbstractTypeExtension
{
    const BASE_ORDER = 50;

    /** @var TaxationSettingsProvider */
    protected $taxationSettingsProvider;

    /** @var TaxManager */
    protected $taxManager;

    /** @var TotalProcessorProvider */
    protected $totalProcessorProvider;

    /** @var SectionProvider */
    protected $sectionProvider;

    /** @var string */
    protected $extendedType;

    /**
     * @param TaxationSettingsProvider $taxationSettingsProvider
     * @param TaxManager $taxManager
     * @param TotalProcessorProvider $totalProcessorProvider
     * @param SectionProvider $sectionProvider
     * @param string $extendedType
     */
    public function __construct(
        TaxationSettingsProvider $taxationSettingsProvider,
        TaxManager $taxManager,
        TotalProcessorProvider $totalProcessorProvider,
        SectionProvider $sectionProvider,
        $extendedType
    ) {
        $this->taxationSettingsProvider = $taxationSettingsProvider;
        $this->taxManager = $taxManager;
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

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->taxationSettingsProvider->isEnabled()) {
            return;
        }

        $this->totalProcessorProvider->enableRecalculation();
    }

    /** {@inheritdoc} */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (!$this->taxationSettingsProvider->isEnabled()) {
            return;
        }

        $sections = [];
        $sectionNames = [
            'taxes' => 'orob2b.tax.order_line_item.taxes.label',
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

        $view->vars['result'] = $this->taxManager->getTax($entity);
    }
}
