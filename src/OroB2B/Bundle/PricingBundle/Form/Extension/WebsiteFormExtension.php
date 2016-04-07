<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

use OroB2B\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteType;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use OroB2B\Bundle\PricingBundle\EventListener\WebsiteListener;

class WebsiteFormExtension extends AbstractTypeExtension
{
    const PRICE_LISTS_TO_WEBSITE_FIELD = 'priceList';
    const PRICE_LISTS_FALLBACK_FIELD = 'fallback';

    /**
     * @var string
     */
    protected $priceListToWebsiteClass;

    /**
     * @var WebsiteListener
     */
    protected $listener;

    /**
     * @param string $priceListToWebsiteClass
     * @param WebsiteListener $listener
     */
    public function __construct(
        $priceListToWebsiteClass,
        WebsiteListener $listener
    ) {
        $this->listener = $listener;
        $this->priceListToWebsiteClass = $priceListToWebsiteClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::PRICE_LISTS_TO_WEBSITE_FIELD,
                PriceListCollectionType::NAME,
                [
                    'allow_add_after' => false,
                    'allow_add' => true,
                    'required' => false,
                    'options' => [
                        'data_class' => $this->priceListToWebsiteClass
                    ]
                ]
            )
            ->add(
                self::PRICE_LISTS_FALLBACK_FIELD,
                'choice',
                [
                    'label' => 'orob2b.pricing.fallback.label',
                    'mapped' => false,
                    'choices' => [
                        PriceListWebsiteFallback::CONFIG =>
                            'orob2b.pricing.fallback.config.label',
                        PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY =>
                            'orob2b.pricing.fallback.current_website_only.label',
                    ],
                ]
            );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this->listener, 'onPostSetData']);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return WebsiteType::NAME;
    }
}
