<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;

class PriceTypeSelectorType extends AbstractType
{
    const NAME = 'orob2b_pricing_price_type';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => [
                    PriceTypeAwareInterface::PRICE_TYPE_UNIT => 'oro.pricing.price_type.unit',
                    PriceTypeAwareInterface::PRICE_TYPE_BUNDLED => 'oro.pricing.price_type.bundled',
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
