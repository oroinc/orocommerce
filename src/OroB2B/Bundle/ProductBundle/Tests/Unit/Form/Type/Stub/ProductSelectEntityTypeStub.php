<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;

class ProductSelectEntityTypeStub extends EntityType
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $choices)
    {
        parent::__construct($choices, ProductSelectType::NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'choice_list'     => $this->choiceList,
            'query_builder'   => null,
            'create_enabled'  => false,
            'class'           => 'OroB2B\Bundle\ProductBundle\Entity\Product',
            'data_parameters' => [],
            'property'        => 'sku',
            'configs'         => [
                'placeholder' => null,
            ],
        ]);
    }
}
