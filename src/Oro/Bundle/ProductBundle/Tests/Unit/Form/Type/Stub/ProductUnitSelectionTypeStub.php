<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as StubEntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductUnitSelectionTypeStub extends StubEntityType
{
    /**
     * {@inheritDoc}
     */
    public function __construct(array $choices = [], $name = ProductUnitSelectionType::NAME)
    {
        parent::__construct($choices, $name);
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(
            [
                'compact' => false,
                'product' => null,
                'product_holder' => null,
                'product_field' => 'product',
                'sell' => null,
            ]
        );
    }
}
