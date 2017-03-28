<?php

namespace Oro\Bundle\PricingBundle\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\PricingBundle\Provider\PriceListProvider;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DefaultPriceListFilterType extends EntityFilterType
{
    const NAME = 'oro_type_default_price_list_filter';

    /**
     * @var PriceListProvider
     */
    protected $priceListProvider;

    /**
     * @param TranslatorInterface $translator
     * @param PriceListProvider $priceListProvider
     */
    public function __construct(TranslatorInterface $translator, PriceListProvider $priceListProvider)
    {
        $this->translator = $translator;
        $this->priceListProvider = $priceListProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setDefaults(
            [
                'default_value' => $this->priceListProvider->getDefaultPriceList()->getName(),
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
