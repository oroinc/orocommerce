<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitRemovedSelectionType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

class StubProductUnitRemovedSelectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return ProductUnitRemovedSelectionType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => 'OroB2B\Bundle\ProductBundle\Entity\ProductUnit',
            'property' => 'code',
            'compact' => false,
            'required' => true,
            'empty_label' => 'orob2b.product.productunit.removed',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ProductUnitSelectionType::NAME;
    }
}
