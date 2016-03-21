<?php

namespace OroB2B\Bundle\TaxBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use OroB2B\Bundle\TaxBundle\Provider\TaxSubtotalProvider;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderLineItemType;

class OrderLineItemTypeExtension extends AbstractTypeExtension
{
    const BASE_ORDER = 50;

    /** @var TaxationSettingsProvider */
    protected $taxationSettingsProvider;

    /** @var TaxManager */
    protected $taxManager;

    /** @var TaxSubtotalProvider */
    protected $taxSubtotalProvider;

    /**
     * @param TaxationSettingsProvider $taxationSettingsProvider
     * @param TaxManager $taxManager
     * @param TaxSubtotalProvider $taxSubtotalProvider
     */
    public function __construct(
        TaxationSettingsProvider $taxationSettingsProvider,
        TaxManager $taxManager,
        TaxSubtotalProvider $taxSubtotalProvider
    ) {
        $this->taxationSettingsProvider = $taxationSettingsProvider;
        $this->taxManager = $taxManager;
        $this->taxSubtotalProvider = $taxSubtotalProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return OrderLineItemType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->taxationSettingsProvider->isEnabled()) {
            return;
        }

        $this->taxSubtotalProvider->setEditMode(true);
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

    /** {@inheritdoc} */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setNormalizer(
            'sections',
            function (Options $options, array $sections) {
                $sectionNames = [
                    'unitPriceIncludingTax' => 'orob2b.tax.order_line_item.unitPrice.includingTax.label',
                    'unitPriceExcludingTax' => 'orob2b.tax.order_line_item.unitPrice.excludingTax.label',
                    'unitPriceTaxAmount' => 'orob2b.tax.order_line_item.unitPrice.taxAmount.label',
                    'rowTotalIncludingTax' => 'orob2b.tax.order_line_item.rowTotal.includingTax.label',
                    'rowTotalExcludingTax' => 'orob2b.tax.order_line_item.rowTotal.excludingTax.label',
                    'rowTotalTaxAmount' => 'orob2b.tax.order_line_item.rowTotal.taxAmount.label',
                    'taxes' => 'orob2b.tax.order_line_item.taxes.label',
                ];

                $order = self::BASE_ORDER;
                foreach ($sectionNames as $sectionName => $label) {
                    $sections[$sectionName] = [
                        'order' => $order++,
                        'label' => $label,
                    ];
                }

                return $sections;
            }
        );
    }
}
