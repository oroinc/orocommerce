<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;

class ProductPriceTest extends WebTestCase
{
    use ProductPriceReference;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadProductPrices::class]);
    }

    public function testCreateDuplicateEntry()
    {
        $productPrice = $this->getPriceByReference('product_price.3');

        $form = $this->getWidgetForm($productPrice->getPriceList());
        $form['oro_action_operation[price][product]'] = $productPrice->getProduct()->getId();
        $form['oro_action_operation[price][quantity]'] = $productPrice->getQuantity();
        $form['oro_action_operation[price][unit]'] = $productPrice->getUnit()->getCode();
        $form['oro_action_operation[price][price][value]'] = $productPrice->getPrice()->getValue();
        $form['oro_action_operation[price][price][currency]'] = $productPrice->getPrice()->getCurrency();

        $crawler = $this->client->submit($form);

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        self::assertStringContainsString(
            'Product has duplication of product prices. ' .
            'Set of fields "PriceList", "Quantity" , "Unit" and "Currency" should be unique.',
            $crawler->html()
        );
    }

    protected function getWidgetForm(PriceList $priceList): Form
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_widget_form',
                [
                    'operationName' => 'oro_pricing_add_product_price',
                    'route' => 'oro_pricing_price_list_view',
                    'entityId' => $priceList->getId(),
                    'entityClass' => PriceList::class
                ]
            ),
            ['_widgetContainer' => 'dialog']
        );

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        return $crawler->selectButton('Save')->form();
    }
}
