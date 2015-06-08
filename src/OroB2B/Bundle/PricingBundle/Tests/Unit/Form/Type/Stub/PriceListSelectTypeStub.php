<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;

class PriceListSelectTypeStub extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return PriceListSelectType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'class' => 'OroB2B\Bundle\PricingBundle\Entity\PriceList',
            'property' => 'name',
            'create_enabled' => false
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
