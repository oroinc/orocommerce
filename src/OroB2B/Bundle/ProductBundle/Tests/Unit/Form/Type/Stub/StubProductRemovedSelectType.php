<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductRemovedSelectType;

class StubProductRemovedSelectType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return ProductRemovedSelectType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => 'OroB2B\Bundle\ProductBundle\Entity\Product',
            'property' => 'sku',
            'create_enabled' => true,
            'grid_name' => 'stub_grid',
            'grid_widget_route' => 'stub_route',
            'configs' => [
                'placeholder' => null,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'entity';
    }
}
