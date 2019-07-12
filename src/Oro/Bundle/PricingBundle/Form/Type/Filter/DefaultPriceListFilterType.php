<?php

namespace Oro\Bundle\PricingBundle\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\PricingBundle\Provider\PriceListProvider;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class DefaultPriceListFilterType extends EntityFilterType
{
    const NAME = 'oro_type_default_price_list_filter';

    /**
     * @var PriceListProvider
     */
    protected $priceListProvider;

    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @var string
     */
    private $priceListClass;

    /**
     * @param TranslatorInterface $translator
     * @param PriceListProvider   $priceListProvider
     * @param ShardManager        $shardManager
     * @param string              $priceListClass
     */
    public function __construct(
        TranslatorInterface $translator,
        PriceListProvider $priceListProvider,
        ShardManager $shardManager,
        $priceListClass
    ) {
        parent::__construct($translator);

        $this->priceListProvider = $priceListProvider;
        $this->shardManager = $shardManager;
        $this->priceListClass = $priceListClass;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $isEntitySharded = $this->shardManager->isShardingEnabled();

        $resolver->setDefaults(
            [
                'field_options' => [
                    'class' => $this->priceListClass,
                    'choice_label' => 'name',
                ],
                'required' => $isEntitySharded,
            ]
        );
    }

    /**
     * {@inheritDoc}
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
