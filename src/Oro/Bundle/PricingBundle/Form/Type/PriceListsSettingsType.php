<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PriceListsSettingsType extends AbstractType
{
    public const NAME = 'oro_pricing_price_lists_settings';

    // fields
    public const PRICE_LIST_COLLECTION_FIELD = 'priceListCollection';
    public const FALLBACK_FIELD = 'fallback';

    // options
    public const FALLBACK_CHOICES = 'fallback_choices';
    public const PRICE_LIST_RELATION_CLASS = 'price_list_relation_class';

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            self::FALLBACK_FIELD,
            ChoiceType::class,
            [
                'label' => 'oro.pricing.fallback.label',
                'mapped' => true,
                'choices' => $options[self::FALLBACK_CHOICES],
            ]
        )
            ->add(
                self::PRICE_LIST_COLLECTION_FIELD,
                PriceListCollectionType::class,
                [
                    'label' => 'oro.pricing.pricelist.entity_plural_label',
                    'mapped' => true,
                    'entry_options' => [
                        'data_class' => $options[self::PRICE_LIST_RELATION_CLASS]
                    ]
                ]
            );
    }

    #[\Override]
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
