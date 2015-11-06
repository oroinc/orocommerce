<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductBySku;
use OroB2B\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use OroB2B\Bundle\ValidationBundle\Validator\Constraints\GreaterThanZero;

class ProductRowType extends AbstractProductAwareType
{
    const NAME = 'orob2b_product_row';

    const PRODUCT_QUANTITY_FIELD_NAME = 'productQuantity';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $productSkuOptions = [
            'required' => false,
            'label' => 'orob2b.product.sku.label',
            'constraints' => [new NotBlank()]
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
                    'label' => 'orob2b.product.quantity.label',
                    'constraints' => [new Decimal(), new GreaterThanZero()]
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
                'validation_required' => false
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
