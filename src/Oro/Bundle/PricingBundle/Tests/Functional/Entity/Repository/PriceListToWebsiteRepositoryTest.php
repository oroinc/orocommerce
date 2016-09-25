<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class PriceListToWebsiteRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations',
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings',
            ]
        );
    }

    public function testFindByPrimaryKey()
    {
        $repository = $this->getRepository();

        /** @var PriceListToWebsite $actualPriceListToWebsite */
        $actualPriceListToWebsite = $repository->findOneBy([]);

        $expectedPriceListToWebsite = $repository->findByPrimaryKey(
            $actualPriceListToWebsite->getPriceList(),
            $actualPriceListToWebsite->getWebsite()
        );

        $this->assertEquals(spl_object_hash($expectedPriceListToWebsite), spl_object_hash($actualPriceListToWebsite));
    }

    /**
     * @dataProvider getPriceListDataProvider
     * @param string $website
     * @param array $expectedPriceLists
     */
    public function testGetPriceLists($website, array $expectedPriceLists)
    {
        /** @var Website $website */
        $website = $this->getReference($website);

        $actualPriceListsToWebsite = $this->getRepository()->getPriceLists($website);

        $actualPriceLists = array_map(
            function (PriceListToWebsite $priceListToWebsite) {
                return $priceListToWebsite->getPriceList()->getName();
            },
            $actualPriceListsToWebsite
        );

        $this->assertEquals($expectedPriceLists, $actualPriceLists);
    }

    /**
     * @return array
     */
    public function getPriceListDataProvider()
    {
        return [
            [
                'website' => 'US',
                'expectedPriceLists' => [
                    'priceList3',
                    'priceList1'
                ]
            ],
            [
                'website' => 'Canada',
                'expectedPriceLists' => [
                    'priceList3'
                ]
            ],
        ];
    }

    public function testGetIteratorByPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $iterator = $this->getRepository()->getIteratorByPriceList($priceList);
        $result = [];
        foreach ($iterator as $item) {
            $result[] = $item;
        }

        $this->assertEquals(
            [
                [
                    'website' => $this->getReference(LoadWebsiteData::WEBSITE1)->getId()
                ],
            ],
            $result
        );
    }

    /**
     * @dataProvider getWebsiteIteratorDataProvider
     * @param array $expectedWebsites
     */
    public function testGetWebsiteIteratorByFallback($expectedWebsites)
    {
        $iterator = $this->getRepository()->getWebsiteIteratorByDefaultFallback(PriceListWebsiteFallback::CONFIG);

        $actualSiteMap = [];
        foreach ($iterator as $website) {
            $actualSiteMap[] = $website->getName();
        }
        $this->assertSame($expectedWebsites, $actualSiteMap);
    }

    /**
     * @return array
     */
    public function getWebsiteIteratorDataProvider()
    {
        return [
            [
                'expectedWebsites' => ['US']
            ]
        ];
    }

    /**
     * @return PriceListToWebsiteRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroPricingBundle:PriceListToWebsite');
    }

    public function testDelete()
    {
        /** @var Website $website */
        $website = $this->getReference('US');
        $this->assertCount(4, $this->getRepository()->findAll());
        $this->assertCount(3, $this->getRepository()->findBy(['website' => $website]));
        $this->getRepository()->delete($website);
        $this->assertCount(1, $this->getRepository()->findAll());
        $this->assertCount(0, $this->getRepository()->findBy(['website' => $website]));
    }
}
