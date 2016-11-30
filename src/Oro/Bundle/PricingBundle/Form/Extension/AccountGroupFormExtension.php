<?php

namespace Oro\Bundle\PricingBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\CustomerBundle\Form\Type\AccountGroupType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListsSettingsType;
use Oro\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use Oro\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use Oro\Bundle\PricingBundle\EventListener\AccountGroupListener;

class AccountGroupFormExtension extends AbstractTypeExtension
{
    /**
     * @var AccountGroupListener
     */
    protected $listener;

    /**
     * @var string
     */
    protected $relationClass = 'Oro\Bundle\PricingBundle\Entity\PriceListToAccountGroup';

    /**
     * @param AccountGroupListener $listener
     */
    public function __construct(AccountGroupListener $listener)
    {
        $this->listener = $listener;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return AccountGroupType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            AccountGroupListener::PRICE_LISTS_COLLECTION_FORM_FIELD_NAME,
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
            PriceListAccountGroupFallback::WEBSITE =>
                'oro.pricing.fallback.website.label',
            PriceListAccountGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY =>
                'oro.pricing.fallback.current_account_group_only.label',
        ];
    }
}
