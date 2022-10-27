<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolationPerTest
 */
class CombinedPriceListToCustomerGroupRepositoryTest extends AbstractCombinedPriceListRelationRepositoryTest
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
        $repo = $registry->getRepository(CombinedPriceListToCustomerGroup::class);
        $combinedPriceListsToCustomerGroup = $repo->findAll();
        $this->assertCount(1, $combinedPriceListsToCustomerGroup);
        //Add Base Relation
        $priceListToCustomer = new PriceListToCustomerGroup();
        /** @var CombinedPriceListToCustomerGroup $combinedPriceListToCustomerGroup */
        $combinedPriceListToCustomerGroup = $this->getRelationByPriceList(
            $combinedPriceListsToCustomerGroup,
            $combinedPriceList
        );
        $priceListToCustomer->setCustomerGroup($combinedPriceListToCustomerGroup->getCustomerGroup());
        $priceListToCustomer->setMergeAllowed(false);
        $priceListToCustomer->setPriceList($priceList);
        $priceListToCustomer->setSortOrder(4);
        $priceListToCustomer->setWebsite($combinedPriceListToCustomerGroup->getWebsite());
        $em->persist($priceListToCustomer);
        $em->flush();
        $repo->deleteInvalidRelations();
        $this->assertCount(1, $repo->findAll());
        //Remove Base Relation
        $em->remove($priceListToCustomer);
        $em->flush();

        $fallback = new PriceListCustomerGroupFallback();
        $fallback->setCustomerGroup($combinedPriceListToCustomerGroup->getCustomerGroup());
        $fallback->setWebsite($combinedPriceListToCustomerGroup->getWebsite());
        $fallback->setFallback(PriceListCustomerGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY);
        $em->persist($fallback);
        $em->flush();

        $repo->deleteInvalidRelations();

        $this->assertCount(1, $repo->findAll());

        $fallback->setFallback(PriceListCustomerGroupFallback::WEBSITE);
        $em->flush();
        $repo->deleteInvalidRelations();

        $this->assertCount(0, $repo->findAll());
    }

    public function testGetWebsitesByCombinedPriceList()
    {
        /** @var  CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference('1t_2t_3t');

        $registry = $this->getContainer()->get('doctrine');
        $repo = $registry->getRepository(CombinedPriceListToCustomerGroup::class);

        $websites = $repo->getWebsitesByCombinedPriceList($combinedPriceList);

        $this->assertEquals([$this->getReference(LoadWebsiteData::WEBSITE1)], $websites);
    }
}
