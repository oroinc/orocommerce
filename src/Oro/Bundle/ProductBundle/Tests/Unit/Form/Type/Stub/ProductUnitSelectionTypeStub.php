<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as StubEntityType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

class ProductUnitSelectionTypeStub extends StubEntityType
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $choices = [], $name = ProductUnitSelectionType::NAME)
    {
        parent::__construct($choices, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'compact' => false,
                'choice_list' => $this->choiceList,
                'product' => null,
                'product_holder' => null,
                'product_field' => 'product',
                'sell' => null,
            ]
        );
    }
}
