<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\PricingBundle\Validator\Constraints\UniquePriceList;

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
            ->add(self::COLLECTION_FIELD_NAME, CollectionType::NAME, [
                'label' => false,
                'type' => PriceListSelectWithPriorityType::NAME,
                'options' => [
                    'data_class' => $this->priceListConfigClassName,
                    'error_bubbling' => false,
                ],
                'handle_primary' => false,
                'allow_add_after' => true,
                'error_bubbling' => false,
                'attr' => [
                    'class' => 'price_lists_collection'
                ],
                'constraints' => [
                    new UniquePriceList()
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
