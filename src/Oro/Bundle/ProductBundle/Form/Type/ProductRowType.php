<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Product row type which is used in quick add form.
 */
class ProductRowType extends AbstractType
{
    const NAME = 'oro_product_row';

    /**
     * @var  ProductUnitsProvider
     */
    protected $productUnitsProvider;

    public function __construct(ProductUnitsProvider $productUnitsProvider)
    {
        $this->productUnitsProvider = $productUnitsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // To keep select consistent with Select2 after page JS initialization have to add first empty option
        $unitChoices = array_merge(['--' => ''], $this->productUnitsProvider->getAvailableProductUnits());

        $builder
            ->add(
                ProductDataStorage::PRODUCT_DISPLAY_NAME,
                ProductAutocompleteType::class,
                [
                    'required' => false,
                    'label' => 'oro.product.sku.label',
                    'mapped' => false
                ]
            )
            ->add(
                ProductDataStorage::PRODUCT_SKU_KEY,
                HiddenType::class
            )
            ->add(
                ProductDataStorage::PRODUCT_UNIT_KEY,
                ProductUnitsType::class,
                [
                    'required' => true,
                    'label' => 'oro.product.productunitprecision.unit.label',
                    'choices' => $unitChoices
                ]
            )
            ->add(
                ProductDataStorage::PRODUCT_QUANTITY_KEY,
                NumberType::class,
                [
                    'required' => false,
                    'label' => 'oro.product.quantity.label',
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
