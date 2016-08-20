<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;

abstract class AbstractProductAwareType extends AbstractType
{
    /** {@inheritdoc} */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'product' => null,
                'product_holder' => null,
                'product_field' => 'product',
            ]
        );

        $resolver->setAllowedTypes('product', ['Oro\Bundle\ProductBundle\Entity\Product', 'null']);
        $resolver->setAllowedTypes(
            'product_holder',
            ['Oro\Bundle\ProductBundle\Model\ProductHolderInterface', 'null']
        );
        $resolver->setAllowedTypes('product_field', 'string');
    }

    /**
     * @param FormInterface $form
     * @return null|Product
     */
    protected function getProduct(FormInterface $form)
    {
        $options = $form->getConfig()->getOptions();
        $productField = $options['product_field'];

        $parent = $form->getParent();
        while ($parent && !$parent->has($productField)) {
            $parent = $parent->getParent();
        }

        if ($parent && $parent->has($productField)) {
            $productData = $parent->get($productField)->getData();
            if ($productData instanceof Product) {
                return $productData;
            }

            if ($productData instanceof ProductHolderInterface) {
                return $productData->getProduct();
            }
        }

        /** @var Product $product */
        $product = $options['product'];
        if ($product) {
            return $product;
        }

        /** @var ProductHolderInterface $productHolder */
        $productHolder = $options['product_holder'];
        if ($productHolder) {
            return $productHolder->getProduct();
        }

        return null;
    }

    /**
     * @param FormView $view
     * @return null|Product
     */
    protected function getProductFromView(FormView $view)
    {
        $product = null;

        if (isset($view->vars['product']) && $view->vars['product']) {
            $product = $view->vars['product'];
        } else {
            $parent = $view->parent;
            while ($parent && !isset($parent->vars['product'])) {
                $parent = $parent->parent;
            }

            if ($parent && isset($parent->vars['product']) && $parent->vars['product']) {
                $product = $parent->vars['product'];
            }
        }

        return $product;
    }

    /**
     * @param FormInterface $form
     * @param FormView $view
     * @return null|Product
     */
    protected function getProductFromFormOrView(FormInterface $form, FormView $view)
    {
        $product = $this->getProduct($form);
        if (!$product) {
            $product = $this->getProductFromView($view);
        }

        return $product;
    }
}
