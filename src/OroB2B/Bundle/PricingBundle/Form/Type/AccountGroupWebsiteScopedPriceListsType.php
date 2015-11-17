<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\AbstractPriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository;

class AccountGroupWebsiteScopedPriceListsType extends AbstractWebsiteScopedPriceListsType
{
    const NAME = 'orob2b_pricing_account_group_website_scoped_price_lists';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return PriceListToAccountGroupRepository
     */
    public function getRepository()
    {
        return $this->getEntityManager()->getRepository('OroB2BPricingBundle:PriceListToAccountGroup');
    }

    /**
     * @param AccountGroup $account
     * @return AbstractPriceListRelation
     */
    public function createPriceListToTargetEntity($account)
    {
        $priceListToTargetEntity = new PriceListToAccountGroup();
        $priceListToTargetEntity->setAccountGroup($account);

        return $priceListToTargetEntity;
    }

    /**
     * @return ObjectManager
     */
    public function getEntityManager()
    {
        return $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListToAccountGroup');
    }
}
