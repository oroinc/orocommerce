<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductBySku;

class ProductRowType extends AbstractProductAwareType
{
    const NAME = 'orob2b_product_row';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $productSkuOptions = [
            'required' => false,
            'label' => 'oro.product.sku.label',
        ];
        if ($options['validation_required']) {
            $productSkuOptions['constraints'][] = new ProductBySku();
        }

        $builder
            ->add(ProductDataStorage::PRODUCT_SKU_KEY, ProductAutocompleteType::NAME, $productSkuOptions)
            ->add(
                ProductDataStorage::PRODUCT_QUANTITY_KEY,
                'number',
                [
                    'required' => false,
                    'label' => 'oro.product.quantity.label',
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_required' => false,
                'data_class'=> 'Oro\Bundle\ProductBundle\Model\ProductRow'
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
    }

    /**
     * {@inheritdoc}
     */
    protected function getProduct(FormInterface $form)
    {
        $product = parent::getProduct($form);
        if (!$product && $form->getParent()) {
            $sku = strtoupper($form->get(ProductDataStorage::PRODUCT_SKU_KEY)->getData());
            $products = $form->getParent()->getConfig()->getOption('products', []);
            if ($products && isset($products[$sku])) {
                $product = $products[$sku];
            }
        }

        return $product;
    }
}
