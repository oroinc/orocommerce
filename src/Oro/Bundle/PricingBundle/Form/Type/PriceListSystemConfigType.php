<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for configuring default price lists in system configuration.
 *
 * Provides a collection form for managing system-wide default price list assignments
 * with priority ordering.
 */
class PriceListSystemConfigType extends AbstractType
{
    const NAME = 'oro_pricing_price_list_system_config';

    /** @var string */
    protected $priceListConfigClassName;

    /** @var string */
    protected $priceListConfigBagClassName;

    /**
     * PriceListSystemConfigType constructor.
     * @param string $priceListConfigClassName
     */
    public function __construct($priceListConfigClassName)
    {
        $this->priceListConfigClassName = $priceListConfigClassName;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entry_type' => PriceListSelectWithPriorityType::class,
            'entry_options' => [
                'data_class' => $this->priceListConfigClassName,
            ],
            'allow_add_after' => false,
            'show_form_when_empty' => true,
            'allow_add' => true,
            'mapped' => true,
            'label' => false,
            'error_bubbling' => false
        ]);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return PriceListCollectionType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }
}
