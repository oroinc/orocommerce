<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PriceListSystemConfigType extends AbstractType
{
    const NAME = 'orob2b_pricing_price_list_system_config';

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

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'type' => PriceListSelectWithPriorityType::NAME,
            'options' => [
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

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return PriceListCollectionType::NAME;
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
        return static::NAME;
    }
}
