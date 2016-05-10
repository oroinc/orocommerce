<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListQueueConsumer;
use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;

/**
 * @dbIsolation
 */
class AjaxProductPriceControllerTest extends AbstractAjaxProductPriceControllerTest
{
    /**
     * @var string
     */
    protected $pricesByAccountActionUrl = 'orob2b_pricing_price_by_account';

    /**
     * @var string
     */
    protected $matchingPriceActionUrl = 'orob2b_pricing_matching_price';

    protected function setUp()
    {
        $this->initClient(
            [],
            array_merge(
                $this->generateBasicAuthHeader(),
                [
                    'HTTP_X-CSRF-Header' => 1,
                    'X-Requested-With' => 'XMLHttpRequest'
                ]
            )
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices',
            ]
        );
    }

    public function testUpdate()
    {
        $this->loadFixtures([
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices'
        ]);
        /** @var ProductPrice $productPrice */
        $productPrice = $this->getReference('product_price.3');
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.bottle');

        $this->disableRealTimeModeCalculate();

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_product_price_update_widget',
                [
                    'id' => $productPrice->getId(),
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid'
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form(
            [
                'orob2b_pricing_price_list_product_price[quantity]' => 10,
                'orob2b_pricing_price_list_product_price[unit]' => $unit->getCode(),
                'orob2b_pricing_price_list_product_price[price][value]' => 20,
                'orob2b_pricing_price_list_product_price[price][currency]' => 'USD'
            ]
        );

        $this->assertSaved($form);
    }

    public function testUpdateDuplicateEntry()
    {
        $this->loadFixtures([
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices'
        ]);
        /** @var ProductPrice $productPrice */
        $productPrice = $this->getReference('product_price.3');
        $productPriceEUR = $this->getReference('product_price.11');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_product_price_update_widget',
                [
                    'id' => $productPriceEUR->getId(),
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid'
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form(
            [
                'orob2b_pricing_price_list_product_price[quantity]' => $productPrice->getQuantity(),
                'orob2b_pricing_price_list_product_price[unit]' => $productPrice->getUnit()->getCode(),
                'orob2b_pricing_price_list_product_price[price][value]' => $productPrice->getPrice()->getValue(),
                'orob2b_pricing_price_list_product_price[price][currency]' => $productPrice->getPrice()->getCurrency(),
            ]
        );

        $this->assertSubmitError($form, 'orob2b.pricing.validators.product_price.unique_entity.message');
    }

    /**
     * @param Form $form
     * @param string $message
     */
    protected function assertSubmitError(Form $form, $message)
    {
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertRegExp('/"savedId":\s*null/i', $html);
        $error = $this->getContainer()->get('translator')
            ->trans($message, [], 'validators');
        $this->assertContains($error, $html);
    }

    /**
     * @param Form $form
     */
    protected function assertSaved(Form $form)
    {
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertRegExp('/"savedId":\s*\d+/i', $html);
    }

    /**
     * @return array
     */
    public function getProductPricesByAccountActionDataProvider()
    {
        return [
            'with account and website' => [
                'product' => 'product.1',
                'expected' => [
                    'bottle' => [
                        ['price' => '1.1000', 'currency' => 'USD', 'qty' => 1],
                    ],
                    'liter' => [
                        ['price' => '1.2000', 'currency' => 'USD', 'qty' => 10]
                    ]
                ],
                'currency' => null,
                'account' => 'account.level_1.1',
                'website' => LoadWebsiteData::WEBSITE1
            ],
            'default, without account and website' => [
                'product' => 'product.1',
                'expected' => [
                    'bottle' => [
                        ['price' => '12.2000', 'currency' => 'EUR', 'qty' => 1],
                        ['price' => '13.1000', 'currency' => 'USD', 'qty' => 1],
                        ['price' => '12.2000', 'currency' => 'EUR', 'qty' => 11],
                    ],
                    'liter' => [
                        ['price' => '10.0000', 'currency' => 'USD', 'qty' => 1],
                        ['price' => '12.2000', 'currency' => 'USD', 'qty' => 10],
                    ],
                ]
            ],

        ];
    }

    /**
     * Disable realtime price calculate mode
     */
    protected function disableRealTimeModeCalculate()
    {
        $configManager = $this->getContainer()->get('oro_config.scope.global');
        $configManager->set(
            Configuration::getConfigKeyByName(
                Configuration::PRICE_LISTS_UPDATE_MODE
            ),
            CombinedPriceListQueueConsumer::MODE_SCHEDULED
        );
        $configManager->flush();
    }
}
