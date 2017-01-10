<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\Traits\ProductAwareTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractProductAwareType extends AbstractType
{
    use ProductAwareTrait;

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
