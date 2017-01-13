<?php

namespace Oro\Bundle\PricingBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\CustomerBundle\Form\Type\CustomerGroupType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListsSettingsType;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use Oro\Bundle\PricingBundle\EventListener\CustomerGroupListener;

class CustomerGroupFormExtension extends AbstractTypeExtension
{
    /**
     * @var CustomerGroupListener
     */
    protected $listener;

    /**
     * @var string
     */
    protected $relationClass = 'Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup';

    /**
     * @param CustomerGroupListener $listener
     */
    public function __construct(CustomerGroupListener $listener)
    {
        $this->listener = $listener;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CustomerGroupType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            CustomerGroupListener::PRICE_LISTS_COLLECTION_FORM_FIELD_NAME,
            WebsiteScopedDataType::NAME,
            [
                'type' => PriceListsSettingsType::NAME,
                'options' => [
                    PriceListsSettingsType::PRICE_LIST_RELATION_CLASS => $this->relationClass,
                    PriceListsSettingsType::FALLBACK_CHOICES => $this->getFallbackChoices(),
                ],
                'label' => false,
                'required' => false,
                'mapped' => false,
                'data' => [],
            ]
        );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this->listener, 'onPostSetData']);
    }

    /**
     * @param string $relationClass
     */
    public function setRelationClass($relationClass)
    {
        $this->relationClass = $relationClass;
    }

    /**
     * @return array
     */
    protected function getFallbackChoices()
    {
        return [
            PriceListCustomerGroupFallback::WEBSITE =>
                'oro.pricing.fallback.website.label',
            PriceListCustomerGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY =>
                'oro.pricing.fallback.current_customer_group_only.label',
        ];
    }
}
