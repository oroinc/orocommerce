<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToAccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;

/**
 * @dbIsolation
 */
class CombinedPriceListToAccountGroupRepositoryTest extends AbstractCombinedPriceListRelationRepositoryTest
{

    public function testDeleteInvalidRelations()
    {
        /** @var  CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference('1t_2t_3t');
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $registry = $this->getContainer()
            ->get('doctrine');
        $em = $registry->getManager();
        $repo = $registry->getRepository('OroB2BPricingBundle:CombinedPriceListToAccountGroup');
        $combinedPriceListsToAccountGroup = $repo->findAll();
        $this->assertCount(1, $combinedPriceListsToAccountGroup);
        //Add Base Relation
        $priceListToAccount = new PriceListToAccountGroup();
        /** @var CombinedPriceListToAccountGroup $combinedPriceListToAccountGroup */
        $combinedPriceListToAccountGroup = $this->getRelationByPriceList(
            $combinedPriceListsToAccountGroup,
            $combinedPriceList
        );
        $priceListToAccount->setAccountGroup($combinedPriceListToAccountGroup->getAccountGroup());
        $priceListToAccount->setMergeAllowed(false);
        $priceListToAccount->setPriceList($priceList);
        $priceListToAccount->setPriority(4);
        $priceListToAccount->setWebsite($combinedPriceListToAccountGroup->getWebsite());
        $em->persist($priceListToAccount);
        $em->flush();
        $repo->deleteInvalidRelations();
        $this->assertCount(1, $repo->findAll());
        //Remove Base Relation
        $em->remove($priceListToAccount);
        $em->flush();

        $fallback = new PriceListAccountGroupFallback();
        $fallback->setAccountGroup($combinedPriceListToAccountGroup->getAccountGroup());
        $fallback->setWebsite($combinedPriceListToAccountGroup->getWebsite());
        $fallback->setFallback(PriceListAccountGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY);
        $em->persist($fallback);
        $em->flush();

        $repo->deleteInvalidRelations();

        $this->assertCount(1, $repo->findAll());

        $fallback->setFallback(PriceListAccountGroupFallback::WEBSITE);
        $em->flush();
        $repo->deleteInvalidRelations();
        
        $this->assertCount(0, $repo->findAll());
    }
}
