<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Form type representing row in {@see QuickAddRowCollectionType}.
 */
class QuickAddRowType extends AbstractType
{
    private ProductUnitsProvider $productUnitsProvider;

    public function __construct(ProductUnitsProvider $productUnitsProvider)
    {
        $this->productUnitsProvider = $productUnitsProvider;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // To keep select consistent with Select2 after page JS initialization have to add first empty option
        $unitChoices = array_merge(['--' => ''], $this->productUnitsProvider->getAvailableProductUnits());

        $builder
            ->add(
                'product',
                ProductAutocompleteType::class,
                [
                    'required' => false,
                    'label' => 'oro.product.sku.label',
                    'mapped' => false,
                ]
            )
            ->add(QuickAddRow::SKU, HiddenType::class)
            ->add(
                QuickAddRow::UNIT,
                ProductUnitsType::class,
                [
                    'required' => true,
                    'label' => 'oro.product.productunitprecision.unit.label',
                    'choices' => $unitChoices,
                ]
            )
            ->add(
                QuickAddRow::QUANTITY,
                NumberType::class,
                [
                    'required' => false,
                    'label' => 'oro.product.quantity.label',
                ]
            );
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view['product']->vars['componentOptions']['selectors'] = [
            'sku' => '[data-name="' . $view['sku']->vars['attr']['data-name'] . '"]',
            'displayName' => '[data-name="' . $view['product']->vars['attr']['data-name'] . '"]',
        ];
    }

    public function getBlockPrefix(): string
    {
        return 'oro_product_quick_add_row';
    }
}
