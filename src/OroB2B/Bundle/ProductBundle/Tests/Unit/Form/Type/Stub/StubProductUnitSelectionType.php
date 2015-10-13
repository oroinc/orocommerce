<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as StubEntityType;

class StubProductUnitSelectionType extends StubEntityType
{
    /** {@inheritdoc} */
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
            ]
        );
    }
}
