<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class QuickAddProductRowType extends AbstractType
{
    const NAME = 'orob2b_product_quick_add_product_row';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(ProductDataStorage::PRODUCT_SKU_KEY, 'hidden')
            ->add(ProductDataStorage::PRODUCT_QUANTITY_KEY, 'hidden');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
