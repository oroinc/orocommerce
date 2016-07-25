<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;

/**
 * @dbIsolation
 */
class CombinedPriceListToAccountRepositoryTest extends AbstractCombinedPriceListRelationRepositoryTest
{

    public function testDeleteInvalidRelations()
    {
        /** @var  CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference('2t_3f_1t');
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $registry = $this->getContainer()
            ->get('doctrine');
        $em = $registry->getManager();
        $repo = $registry->getRepository('OroB2BPricingBundle:CombinedPriceListToAccount');
        $combinedPriceListsToAccount = $repo->findAll();
        $this->assertCount(2, $combinedPriceListsToAccount);
        //Add Base Relation
        $priceListToAccount = new PriceListToAccount();
        /** @var CombinedPriceListToAccount $combinedPriceListToAccount */
        $combinedPriceListToAccount = $this->getRelationByPriceList($combinedPriceListsToAccount, $combinedPriceList);
        $priceListToAccount->setAccount($combinedPriceListToAccount->getAccount());
        $priceListToAccount->setMergeAllowed(false);
        $priceListToAccount->setPriceList($priceList);
        $priceListToAccount->setPriority(4);
        $priceListToAccount->setWebsite($combinedPriceListToAccount->getWebsite());
        $em->persist($priceListToAccount);
        $em->flush();
        $repo->deleteInvalidRelations();
        $this->assertCount(1, $repo->findAll());
        //Remove Base Relation
        $em->remove($priceListToAccount);
        $em->flush();

        $fallback = new PriceListAccountFallback();
        $fallback->setAccount($combinedPriceListToAccount->getAccount());
        $fallback->setWebsite($combinedPriceListToAccount->getWebsite());
        $fallback->setFallback(PriceListAccountFallback::CURRENT_ACCOUNT_ONLY);
        $em->persist($fallback);
        $em->flush();

        $repo->deleteInvalidRelations();

        $this->assertCount(1, $repo->findAll());

        $fallback->setFallback(PriceListAccountFallback::ACCOUNT_GROUP);
        $em->flush();
        $repo->deleteInvalidRelations();

        $this->assertCount(0, $repo->findAll());
    }
}
