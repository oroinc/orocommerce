<?php

namespace Oro\Bundle\ProductBundle\Form\Type\Traits;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Symfony\Component\Form\FormInterface;

trait ProductAwareTrait
{
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
}
