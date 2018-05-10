<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PriceTypeSelectorType extends AbstractType
{
    const NAME = 'oro_pricing_price_type';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                // TODO: remove 'choices_as_values' option below in scope of BAP-15236
                'choices_as_values' => true,
                'choices' => [
                    'oro.pricing.price_type.unit' => PriceTypeAwareInterface::PRICE_TYPE_UNIT,
                    'oro.pricing.price_type.bundled' => PriceTypeAwareInterface::PRICE_TYPE_BUNDLED,
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
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
