<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller\Frontend;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterType;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
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
                        ['price' => 13.1, 'currency' => 'USD', 'qty' => 1],
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
                    ],
                    'bottle' => [
                        ['price' => 13.1, 'currency' => 'USD', 'qty' => 1],
                    ],
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
     * @dataProvider filterDataProvider
     * @param array $filter
     * @param array $expected
     */
    public function testGridFilter(array $filter, array $expected)
    {
        $account = $this->getReference('account.level_1.2');
        $response = $this->client->requestGrid([
            'gridName' => 'frontend-products-grid',
            PriceListRequestHandler::ACCOUNT_ID_KEY => $account->getId(),
        ], $filter);
        $result = $this->getJsonResponseContent($response, 200);

        $this->assertArrayHasKey('data', $result);
        $this->assertSameSize($expected, $result['data']);

        foreach ($result['data'] as $product) {
            $this->assertContains($product['sku'], $expected);
        }
    }

    /**
     * @dataProvider setCurrentCurrencyDataProvider
     * @param string $currency
     * @param string $expectedResult
     */
    public function testSetCurrentCurrencyAction($currency, $expectedResult)
    {
        $params = ['currency' => $currency];
        $this->client->request('POST', $this->getUrl('orob2b_pricing_frontend_set_current_currency'), $params);
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);
        $this->assertSame($expectedResult, $data);
    }

    /**
     * @return array
     */
    public function setCurrentCurrencyDataProvider()
    {
        return [
            [
                'currency' => 'USD',
                'expectedResult' => ['success' => true] ,
            ],
            [
                'currency' => 'USD2',
                'expectedResult' => ['success' => false] ,
            ],
        ];
    }

    /**
     * @return array
     */
    public function filterDataProvider()
    {
        return [
            'equal 1.1 USD per bottle' => [
                'filter' => [
                    'frontend-products-grid[_filter][minimum_price][value]' => 1.1,
                    'frontend-products-grid[_filter][minimum_price][type]'  => null,
                    'frontend-products-grid[_filter][minimum_price][unit]'  => 'bottle'
                ],
                'expected' => []
            ],
            'equal 10 USD per liter' => [
                'filter' => [
                    'frontend-products-grid[_filter][minimum_price][value]' => 10,
                    'frontend-products-grid[_filter][minimum_price][type]'  => null,
                    'frontend-products-grid[_filter][minimum_price][unit]'  => 'liter'
                ],
                'expected' => ['product.1']
            ],
            'greater equal 12.2 USD per liter' => [
                'filter' => [
                    'frontend-products-grid[_filter][minimum_price][value]' => 12.2,
                    'frontend-products-grid[_filter][minimum_price][type]' => NumberFilterType::TYPE_GREATER_EQUAL,
                    'frontend-products-grid[_filter][minimum_price][unit]' => 'liter'
                ],
                'expected' => ['product.1', 'product.2']
            ],
            'less 10 USD per liter' => [
                'filter' => [
                    'frontend-products-grid[_filter][minimum_price][value]' => 10,
                    'frontend-products-grid[_filter][minimum_price][type]'  => NumberFilterType::TYPE_LESS_THAN,
                    'frontend-products-grid[_filter][minimum_price][unit]'  => 'liter'
                ],
                'expected' => ['product.3']
            ],
            'greater 10 USD per liter AND less 20 EUR per bottle' => [
                'filter' => [
                    'frontend-products-grid[_filter][minimum_price][value]' => 1,
                    'frontend-products-grid[_filter][minimum_price][value_end]' => 10,
                    'frontend-products-grid[_filter][minimum_price][type]'  => NumberRangeFilterType::TYPE_BETWEEN,
                    'frontend-products-grid[_filter][minimum_price][unit]'  => 'liter',
                ],
                'expected' => ['product.1', 'product.3']
            ],
        ];
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param Website $website
     */
    protected function setPriceListToDefaultWebsite(CombinedPriceList $combinedPriceList, Website $website)
    {
        $priceListToWebsite = $this->priceListToWebsiteRepository
            ->findOneBy(['website' => $website, 'priceList' => $combinedPriceList]);

        if (!$priceListToWebsite) {
            $priceListToWebsite = $this->priceListToWebsiteRepository
                ->findOneBy(['website' => $website]);
        }
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
