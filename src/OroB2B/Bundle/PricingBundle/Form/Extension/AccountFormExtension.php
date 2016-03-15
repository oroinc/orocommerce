<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountType;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use OroB2B\Bundle\PricingBundle\EventListener\AccountListener;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListsSettingsType;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;

class AccountFormExtension extends AbstractTypeExtension
{
    /**
     * @var AccountListener
     */
    protected $listener;

    /**
     * @var string
     */
    protected $relationClass = 'OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount';

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
                'orob2b.pricing.fallback.account_group.label',
            PriceListAccountFallback::CURRENT_ACCOUNT_ONLY =>
                'orob2b.pricing.fallback.current_account_only.label',
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
