<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolationPerTest
 */
class CombinedPriceListToWebsiteRepositoryTest extends AbstractCombinedPriceListRelationRepositoryTest
{
    public function testDeleteInvalidRelations()
    {
        /** @var  CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference('1t_2t_3t');
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $registry = $this->getContainer()->get('doctrine');
        $em = $registry->getManager();
        $repo = $registry->getRepository(CombinedPriceListToWebsite::class);
        $combinedPriceListsToWebsite = $repo->findAll();
        $this->assertCount(3, $combinedPriceListsToWebsite);
        //Add Base Relation
        $priceListToWebsite = new PriceListToWebsite();
        /** @var CombinedPriceListToWebsite $combinedPriceListToWebsite */
        $combinedPriceListToWebsite = $this->getRelationByPriceList($combinedPriceListsToWebsite, $combinedPriceList);
        $priceListToWebsite->setWebsite($combinedPriceListToWebsite->getWebsite());
        $priceListToWebsite->setMergeAllowed(false);
        $priceListToWebsite->setPriceList($priceList);
        $priceListToWebsite->setSortOrder(4);
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

    public function testGetWebsitesByCombinedPriceList()
    {
        /** @var  CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference('1t_2t_3t');

        $registry = $this->getContainer()->get('doctrine');
        $repo = $registry->getRepository(CombinedPriceListToWebsite::class);

        $websites = $repo->getWebsitesByCombinedPriceList($combinedPriceList);

        $this->assertEquals([$this->getReference(LoadWebsiteData::WEBSITE1)], $websites);
    }
}
