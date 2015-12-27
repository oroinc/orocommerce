<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountGroupType;
use OroB2B\Bundle\PricingBundle\Form\Type\AccountGroupWebsiteScopedPriceListsType;

class AccountGroupFormExtension extends AbstractTypeExtension
{
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
            'priceListsByWebsites',
            AccountGroupWebsiteScopedPriceListsType::NAME
        );
    }
}
