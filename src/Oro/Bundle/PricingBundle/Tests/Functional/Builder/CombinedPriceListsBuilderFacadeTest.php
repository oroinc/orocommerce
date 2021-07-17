<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Builder;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListsBuilderFacade;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelationsForCPLBuilderFacade;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolationPerTest
 */
class CombinedPriceListsBuilderFacadeTest extends WebTestCase
{
    /**
     * @var CombinedPriceListsBuilderFacade
     */
    private $facade;

    /**
     * @var CombinedPriceListRepository
     */
    private $cplRepo;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->facade = $this->getContainer()->get('oro_pricing.builder.combined_price_list_builder_facade');
        $this->cplRepo = $this->getContainer()->get('doctrine')
            ->getManagerForClass(CombinedPriceList::class)
            ->getRepository(CombinedPriceList::class);

        $this->loadFixtures([
            LoadPriceListRelationsForCPLBuilderFacade::class,
        ]);
    }

    public function testRebuildAll()
    {
        $this->removeAllCpls();
        $this->facade->rebuildAll(time());

        $allCpls = $this->cplRepo->findAll();

        $this->assertCount(19, $allCpls); // 1 config + 2 websites + 4 customer groups + 12 customers
    }

    public function testRebuildForWebsites()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);

        $this->removeAllCpls();
        $this->facade->rebuildForWebsites([$website], time());

        $allCpls = $this->cplRepo->findAll();

        /**
         * Expected: 4
         * 1 website
         * 1 customer group with fallback to website
         * 2 customers:
         *   1 for customer with fallback to website,
         *   1 for customer with default fallback (to group) and without group
         */
        $this->assertCount(4, $allCpls);
    }

    public function testRebuildForCustomerGroups()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference(LoadGroups::GROUP1);

        $this->removeAllCpls();
        $this->facade->rebuildForCustomerGroups([$customerGroup], $website, time());

        $allCpls = $this->cplRepo->findAll();

        $this->assertCount(2, $allCpls); // 1 group + 1 customer with fallback to group
    }

    public function testRebuildForCustomers()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        /** @var Customer $customerGroup */
        $customer = $this->getReference(LoadCustomers::CUSTOMER_LEVEL_1_1);

        $this->removeAllCpls();
        $this->facade->rebuildForCustomers([$customer], $website, time());

        $allCpls = $this->cplRepo->findAll();

        $this->assertCount(1, $allCpls); // 1 customer
    }

    /**
     * @dataProvider priceListDataProvider
     * @param string $plReference
     * @param int $expectedCpls
     */
    public function testRebuildForPriceLists($plReference, $expectedCpls)
    {
        /** @var PriceList $priceList */
        if ($plReference === 'default') {
            $priceList = $this->getContainer()->get('doctrine')
                ->getManagerForClass(PriceList::class)
                ->getRepository(PriceList::class)
                ->findOneBy(['name' => 'Default Price List']);
        } else {
            $priceList = $this->getReference($plReference);
        }

        $this->removeAllCpls();
        $this->facade->rebuildForPriceLists([$priceList], time());

        $allCpls = $this->cplRepo->findAll();

        $this->assertCount($expectedCpls, $allCpls);
    }

    public function priceListDataProvider(): array
    {
        return [
            // 1 config, 1 website, 1 group, 2 customers (1 fb to group +  1 without group and with default fb)
            'change on config level' => ['default', 5],
            // 1 website, 1 group, 2 customers (1 fb to group + 1 without group and with default fb)
            'change on website level' => ['PL_WS1', 4],
            'change on customer group level' => ['PL_WS2_CG1', 2], // 1 group, 1 customer (fallback to group)
            'change on customer level' => ['PL_WS1_C11', 1] // 1 customer
        ];
    }

    private function removeAllCpls(): void
    {
        $this->cplRepo->createQueryBuilder('pl')
            ->delete(CombinedPriceList::class)
            ->getQuery()
            ->execute();
    }
}
