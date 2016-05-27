<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\FormInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductUnitSelectionType extends AbstractProductUnitSelectionType
{
    const NAME = 'orob2b_product_unit_selection';

    /**
     * @param FormInterface $form
     * @param Product|null $product
     * @return ProductUnit[]
     */
    protected function getProductUnits(FormInterface $form, Product $product = null)
    {
        $this->sell = false;
        return parent::getProductUnits($form, $product);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
