<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub;

use OroB2B\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;


class PriceListCollectionTypeStub extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return PriceListCollectionType::NAME;
    }
//
//    /**
//     * {@inheritdoc}
//     */
//    public function configureOptions(OptionsResolver $resolver)
//    {
//        $resolver->setDefaults(
//            [
//                'class' => 'OroB2B\Bundle\PricingBundle\Entity\PriceList',
//                'property' => 'name',
//                'create_enabled' => false
//            ]
//        );
//    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
