<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite;
use OroB2B\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;

/**
 * @dbIsolation
 */
class CombinedPriceListToWebsiteRepositoryTest extends AbstractCombinedPriceListRelationRepositoryTest
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
        $repo = $registry->getRepository('OroB2BPricingBundle:CombinedPriceListToWebsite');
        $combinedPriceListsToWebsite = $repo->findAll();
        $this->assertCount(3, $combinedPriceListsToWebsite);
        //Add Base Relation
        $priceListToWebsite = new PriceListToWebsite();
        /** @var CombinedPriceListToWebsite $combinedPriceListToWebsite */
        $combinedPriceListToWebsite = $this->getRelationByPriceList($combinedPriceListsToWebsite, $combinedPriceList);
        $priceListToWebsite->setWebsite($combinedPriceListToWebsite->getWebsite());
        $priceListToWebsite->setMergeAllowed(false);
        $priceListToWebsite->setPriceList($priceList);
        $priceListToWebsite->setPriority(4);
        $em->persist($priceListToWebsite);
        $em->flush();
        $repo->deleteInvalidRelations();
        $this->assertCount(1, $repo->findAll());
        //Remove Base Relation
        $em->remove($priceListToWebsite);
        $em->flush();

        $fallback = new PriceListWebsiteFallback();
        $fallback->setWebsite($priceListToWebsite->getWebsite());
        $fallback->setFallback(PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY);
        $em->persist($fallback);
        $em->flush();

        $repo->deleteInvalidRelations();

        $this->assertCount(1, $repo->findAll());

        $fallback->setFallback(PriceListWebsiteFallback::CONFIG);
        $em->flush();
        $repo->deleteInvalidRelations();

        $this->assertCount(0, $repo->findAll());
    }
}
