<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolationPerTest
 */
class PriceListToWebsiteRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadPriceListRelations::class,
                LoadPriceListFallbackSettings::class,
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
        $result1 = iterator_to_array($this->getRepository()->getIteratorByPriceList($priceList));
        $result2 = iterator_to_array($this->getRepository()->getIteratorByPriceLists([$priceList]));

        $this->assertEquals(
            [
                [
                    'website' => $this->getReference(LoadWebsiteData::WEBSITE1)->getId()
                ],
            ],
            $result1
        );
        $this->assertSame($result1, $result2);
    }

    /**
     * @dataProvider getWebsiteIteratorDataProvider
     * @param array $expectedWebsites
     */
    public function testGetWebsiteIteratorWithDefaultFallback($expectedWebsites)
    {
        $iterator = $this->getRepository()->getWebsiteIteratorWithDefaultFallback();

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
                'expectedWebsites' => ['Default', 'US', 'CA']
            ]
        ];
    }

    /**
     * @return PriceListToWebsiteRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(PriceListToWebsite::class);
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

    /**
     * @dataProvider assignedPriceListsDataProvider
     * @param string $websiteReference
     * @param bool $expected
     */
    public function testHasAssignedPriceLists($websiteReference, $expected)
    {
        /** @var Website $website */
        $website = $this->getReference($websiteReference);

        $this->assertEquals($expected, $this->getRepository()->hasAssignedPriceLists($website));
    }

    public function assignedPriceListsDataProvider(): array
    {
        return [
            ['US', true],
            ['CA', false]
        ];
    }
}
