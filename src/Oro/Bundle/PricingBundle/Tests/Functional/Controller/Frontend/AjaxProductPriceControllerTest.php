<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Controller\Frontend;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\Controller\AbstractAjaxProductPriceControllerTest;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class AjaxProductPriceControllerTest extends AbstractAjaxProductPriceControllerTest
{
    /**
     * @var string
     */
    protected $pricesByCustomerActionUrl = 'oro_pricing_frontend_price_by_customer';

    /**
     * @var string
     */
    protected $matchingPriceActionUrl = 'oro_pricing_frontend_matching_price';

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

    /**
     * @var Client
     */
    protected $client;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                LoadCombinedProductPrices::class
            ]
        );

        $registry = $this->getContainer()->get('doctrine');

        $this->manager = $registry->getManagerForClass(CombinedPriceListToWebsite::class);
        $this->priceListToWebsiteRepository = $this->manager->getRepository(CombinedPriceListToWebsite::class);

        $this->websiteRepository = $registry->getManagerForClass(Website::class)->getRepository(Website::class);
        $this->combinedPriceListRepository = $registry->getManagerForClass(CombinedPriceList::class)
            ->getRepository(CombinedPriceList::class);
    }

    /**
     * @return array
     */
    public function getProductPricesByCustomerActionDataProvider()
    {
        return [
            'without currency (all available currencies)' => [
                'product' => 'product-1',
                'expected' => [
                    ['price' => 10.0000, 'currency' => 'USD', 'quantity' => 1, 'unit' => 'liter'],
                    ['price' => 12.2000, 'currency' => 'USD', 'quantity' => 10, 'unit' => 'liter'],
                    ['price' => 13.1, 'currency' => 'USD', 'quantity' => 1, 'unit' => 'bottle'],
                ],
            ],
            'with currency' => [
                'product' => 'product-1',
                'expected' => [
                    ['price' => 10.0000, 'currency' => 'USD', 'quantity' => 1, 'unit' => 'liter'],
                    ['price' => 12.2000, 'currency' => 'USD', 'quantity' => 10, 'unit' => 'liter'],
                    ['price' => 13.1, 'currency' => 'USD', 'quantity' => 1, 'unit' => 'bottle'],
                ],
                'currency' => 'USD'
            ]
        ];
    }

    /**
     * @dataProvider setCurrentCurrencyDataProvider
     * @param string $currency
     * @param string $expectedResult
     */
    public function testSetCurrentCurrencyAction($currency, $expectedResult)
    {
        $params = ['currency' => $currency];
        $this->ajaxRequest('POST', $this->getUrl('oro_pricing_frontend_set_current_currency'), $params);
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
