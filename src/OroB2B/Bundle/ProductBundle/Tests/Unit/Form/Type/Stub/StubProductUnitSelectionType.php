<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as StubEntityType;

class StubProductUnitSelectionType extends StubEntityType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'compact' => false,
            'choice_list' => $this->choiceList
        ]);
    }
}
