<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;

class PriceListsSettingsType extends AbstractType
{
    const NAME = 'oro_pricing_price_lists_settings';

    // fields
    const PRICE_LIST_COLLECTION_FIELD = 'priceListCollection';
    const FALLBACK_FIELD = 'fallback';

    // options
    const FALLBACK_CHOICES = 'fallback_choices';
    const PRICE_LIST_RELATION_CLASS = 'price_list_relation_class';

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

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            self::FALLBACK_FIELD,
            'choice',
            [
                'label' => 'oro.pricing.fallback.label',
                'mapped' => true,
                'choices' => $options[self::FALLBACK_CHOICES],
            ]
        )
            ->add(
                self::PRICE_LIST_COLLECTION_FIELD,
                PriceListCollectionType::NAME,
                [
                    'label' => 'oro.pricing.pricelist.entity_plural_label',
                    'mapped' => true,
                    'options' => [
                        'data_class' => $options[self::PRICE_LIST_RELATION_CLASS]
                    ]
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'render_as_widget' => true,
                'label' => false,
                'ownership_disabled' => true,
                WebsiteScopedDataType::WEBSITE_OPTION => null
            ]
        );
        $resolver->setRequired(
            [
                self::FALLBACK_CHOICES,
                self::PRICE_LIST_RELATION_CLASS
            ]
        );
    }
}
