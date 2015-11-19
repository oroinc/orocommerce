<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\AccountBundle\Entity\Account;

class AccountWebsiteScopedPriceListsType extends AbstractWebsiteScopedPriceListsType
{
    const NAME = 'orob2b_pricing_account_website_scoped_price_lists';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return PriceListToAccountRepository
     */
    protected function getRepository()
    {
        return $this->getEntityManager()->getRepository('OroB2BPricingBundle:PriceListToAccount');
    }

    /**
     * @param Account $account
     * @return BasePriceListRelation
     */
    protected function createPriceListToTargetEntity($account)
    {
        $priceListToTargetEntity = new PriceListToAccount();
        $priceListToTargetEntity->setAccount($account);

        return $priceListToTargetEntity;
    }

    /**
     * @return ObjectManager
     */
    protected function getEntityManager()
    {
        return $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListToAccount');
    }
}
