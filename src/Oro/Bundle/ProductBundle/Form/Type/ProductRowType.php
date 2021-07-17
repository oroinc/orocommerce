<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductBySku;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Product row type which is used in quick add form.
 */
class ProductRowType extends AbstractProductAwareType
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
        $productSkuOptions = [];
        if ($options['validation_required']) {
            $productSkuOptions['constraints'][] = new ProductBySku();
        }

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
                HiddenType::class,
                $productSkuOptions
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_required' => false,
                'data_class' => 'Oro\Bundle\ProductBundle\Model\ProductRow'
            ]
        );
        $resolver->setAllowedTypes('validation_required', 'bool');
        parent::configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['product'] = $this->getProductFromFormOrView($form, $view);
        $view->vars['name'] = '__name__';
    }

    /**
     * {@inheritdoc}
     */
    protected function getProduct(FormInterface $form)
    {
        $product = parent::getProduct($form);
        if (!$product && $form->getParent()) {
            $sku = mb_strtoupper($form->get(ProductDataStorage::PRODUCT_SKU_KEY)->getData());
            $products = $form->getParent()->getConfig()->getOption('products', []);
            if ($products && isset($products[$sku])) {
                $product = $products[$sku];
            }
        }

        return $product;
    }
}
