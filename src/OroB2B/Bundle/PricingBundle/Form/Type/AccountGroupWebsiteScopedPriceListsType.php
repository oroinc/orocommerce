<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\FormEvent;

class AccountGroupWebsiteScopedPriceListsType extends AbstractWebsiteScopedPriceListsType
{
    const NAME = 'orob2b_pricing_account_group_website_scoped_price_lists';

    /**
     * {@inheritdoc}
     */
    public function onPostSetData(FormEvent $event)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
