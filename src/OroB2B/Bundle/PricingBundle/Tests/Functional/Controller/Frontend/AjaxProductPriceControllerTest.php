<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller\Frontend;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use OroB2B\Bundle\PricingBundle\Tests\Functional\Controller\AbstractAjaxProductPriceControllerTest;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class AjaxProductPriceControllerTest extends AbstractAjaxProductPriceControllerTest
{
    /**
     * @var string
     */
    protected $pricesByAccountActionUrl = 'orob2b_pricing_frontend_price_by_account';

    /**
     * @var string
     */
    protected $matchingPriceActionUrl = 'orob2b_pricing_frontend_matching_price';

    /**
     * @var PriceListToWebsiteRepository
     */
    protected $priceListToWebsiteRepository;

    /**
     * @var CombinedPriceListRepository
     */
    protected $combinedPriceListRepository;

    /**
     * @var WebsiteRepository
     */
    protected $websiteRepository;

    /**
     * @var ObjectManager
     */
    protected $manager;


    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices',
            ]
        );

        $priceListClass = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite';
        $cplClass = 'OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList';
        $websiteClass = 'OroB2B\Bundle\WebsiteBundle\Entity\Website';
        $registry = $this->getContainer()->get('doctrine');

        $this->manager = $registry->getManagerForClass($priceListClass);
        $this->priceListToWebsiteRepository = $this->manager->getRepository($priceListClass);

        $this->websiteRepository = $registry->getManagerForClass($websiteClass)->getRepository($websiteClass);
        $this->combinedPriceListRepository = $registry->getManagerForClass($websiteClass)->getRepository($cplClass);
    }

    /**
     * @return array
     */
    public function getProductPricesByAccountActionDataProvider()
    {
        return [
            'without currency' => [
                'product' => 'product.1',
                'expected' => [
                    'bottle' => [
                        ['price' => 12.2, 'currency' => 'EUR', 'qty' => 1],
                        ['price' => 12.2, 'currency' => 'EUR', 'qty' => 11],
                    ],
                    'liter' => [
                        ['price' => 10, 'currency' => 'USD', 'qty' => 1],
                        ['price' => 12.2, 'currency' => 'USD', 'qty' => 10],
                    ]
                ],
            ],
            'with currency' => [
                'product' => 'product.1',
                'expected' => [
                    'liter' => [
                        ['price' => 10.0000, 'currency' => 'USD', 'qty' => 1],
                        ['price' => 12.2000, 'currency' => 'USD', 'qty' => 10],
                    ]
                ],
                'currency' => 'USD'
            ]
        ];
    }

    /**
     * @dataProvider unitDataProvider
     * @param string $priceList
     * @param string $product
     * @param null|string $currency
     * @param array $expected
     */
    public function testGetProductUnitsByCurrencyAction($priceList, $product, $currency = null, array $expected = [])
    {
        /** @var CombinedPriceList $priceList */
        $priceList = $this->getReference($priceList);
        /** @var Product $product */
        $product = $this->getReference($product);
        /** @var Website $website */
        $website = $this->websiteRepository->getDefaultWebsite();
        $priceList = $this->combinedPriceListRepository->find($priceList->getId());
        $this->setPriceListToDefaultWebsite($priceList, $website);

        $params = [
            'id' => $product->getId(),
            'currency' => $currency
        ];

        $this->client->request('GET', $this->getUrl('orob2b_pricing_frontend_units_by_pricelist', $params));

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('units', $data);
        $this->assertEquals($expected, array_keys($data['units']));
    }

    /**
     * @return array
     */
    public function unitDataProvider()
    {
        return [
            [
                '1f',
                'product.1',
                null,
                ['bottle', 'liter']
            ],
            [
                '1f',
                'product.1',
                'EUR',
                ['bottle']
            ]
        ];
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param Website $website
     */
    protected function setPriceListToDefaultWebsite(CombinedPriceList $combinedPriceList, Website $website)
    {
        $priceListToWebsite = $this->priceListToWebsiteRepository
            ->findOneBy(['website' => $website]);
        if (!$priceListToWebsite) {
            $priceListToWebsite = new CombinedPriceListToWebsite();
            $priceListToWebsite->setWebsite($website);
            $priceListToWebsite->setPriceList($combinedPriceList);
            $this->manager->persist($priceListToWebsite);
        }
        $priceListToWebsite->setPriceList($combinedPriceList);
        $this->manager->flush();
    }
}
