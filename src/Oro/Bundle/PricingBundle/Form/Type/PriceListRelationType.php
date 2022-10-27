<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Price list relation form type to assign price lists to some entities
 */
class PriceListRelationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('priceList', PriceListSelectType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'website' => null,
            'data_class' => null,
            'ownership_disabled' => true,
        ]);

        $resolver->setAllowedTypes('website', ['null', Website::class]);
    }
}
