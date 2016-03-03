<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PriceListsSettingsType extends AbstractType
{
    const NAME = 'orob2b_pricing_price_lists_settings';

    const PRICE_LIST_COLLECTION_FIELD = 'priceListCollection';
    const FALLBACK_FIELD = 'fallback';
    const PRICE_LIST_RELATION_CLASS = 'price_list_relation_class';
    const FALLBACK_CHOICES = 'fallback_choices';

    /**
     * {@inheritdoc}
     */
    public function getName()
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
                'label' => 'orob2b.pricing.fallback.label',
                'mapped' => true,
                'choices' => $options[self::FALLBACK_CHOICES],
            ]
        )
            ->add(
                self::PRICE_LIST_COLLECTION_FIELD,
                PriceListCollectionType::NAME,
                [
                    'label' => 'orob2b.pricing.pricelist.entity_plural_label',
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
