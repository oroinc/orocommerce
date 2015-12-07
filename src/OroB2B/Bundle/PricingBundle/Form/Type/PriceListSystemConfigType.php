<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PriceListSystemConfigType extends AbstractType
{
    const NAME = 'orob2b_pricing_price_list_system_config';

    /** @var  string */
    protected $priceListConfigClassName;

    /** @var  string */
    protected $priceListConfigBagClassName;

    /**
     * PriceListSystemConfigType constructor.
     * @param $priceListConfigClassName
     */
    public function __construct($priceListConfigClassName)
    {
        $this->priceListConfigClassName = $priceListConfigClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        $options->setDefaults([
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
        return static::NAME;
    }
}
