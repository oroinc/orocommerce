<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductUnitSelectionTypeStub extends EntityTypeStub
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'compact' => false,
            'product' => null,
            'product_holder' => null,
            'product_field' => 'product',
            'sell' => null,
        ]);
    }
}
