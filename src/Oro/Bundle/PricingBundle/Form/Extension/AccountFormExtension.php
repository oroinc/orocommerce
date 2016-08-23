<?php

namespace Oro\Bundle\PricingBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\AccountBundle\Form\Type\AccountType;
use Oro\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use Oro\Bundle\PricingBundle\EventListener\AccountListener;
use Oro\Bundle\PricingBundle\Form\Type\PriceListsSettingsType;
use Oro\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;

class AccountFormExtension extends AbstractTypeExtension
{
    /**
     * @var AccountListener
     */
    protected $listener;

    /**
     * @var string
     */
    protected $relationClass = 'Oro\Bundle\PricingBundle\Entity\PriceListToAccount';

    /**
     * @param AccountListener $listener
     */
    public function __construct(AccountListener $listener)
    {
        $this->listener = $listener;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return AccountType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            AccountListener::PRICE_LISTS_COLLECTION_FORM_FIELD_NAME,
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
     * @return array
     */
    protected function getFallbackChoices()
    {
        return [
            PriceListAccountFallback::ACCOUNT_GROUP =>
                'oro.pricing.fallback.account_group.label',
            PriceListAccountFallback::CURRENT_ACCOUNT_ONLY =>
                'oro.pricing.fallback.current_account_only.label',
        ];
    }

    /**
     * @param string $relationClass
     */
    public function setRelationClass($relationClass)
    {
        $this->relationClass = $relationClass;
    }
}
