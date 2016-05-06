<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite;
use OroB2B\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

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
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings',
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
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:PriceListToWebsite');
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
