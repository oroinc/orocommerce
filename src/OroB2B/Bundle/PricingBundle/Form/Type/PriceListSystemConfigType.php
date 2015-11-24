<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PriceListSystemConfigType extends AbstractType
{
    const NAME = 'orob2b_pricing_price_list_system_config';
    const COLLECTION_FIELD_NAME = 'configs';

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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(self::COLLECTION_FIELD_NAME, PriceListCollectionType::NAME, [
                'type' => PriceListSelectWithPriorityType::NAME,
                'options' => [
                    'data_class' => $this->priceListConfigClassName,
                ],
                'allow_add_after' => false,
                'allow_add' => true,
                'mapped' => true,
                'attr' => [
                    'class' => 'price_lists_collection'
                ]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $options)
    {
        $options->setDefaults([
            'label' => false,
            'error_bubbling' => false
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
