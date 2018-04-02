<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type\Stub;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as StubEntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityType extends StubEntityType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'class' => '',
            'choice_label' => '',
            'choice_list' => $this->choiceList,
            'configs' => [],
        ]);
    }
}
