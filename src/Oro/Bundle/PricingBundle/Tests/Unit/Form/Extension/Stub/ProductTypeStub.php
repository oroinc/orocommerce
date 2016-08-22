<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductTypeStub extends AbstractType
{
    /**
     * @return string
     */
    public function getName()
    {
        return ProductType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
