<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;

/**
 * @dbIsolation
 */
class ProductPriceTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices'
            ]
        );
    }

    public function testCreateDuplicateEntry()
    {
        /** @var ProductPrice $productPrice */
        $productPrice = $this->getReference('product_price.3');

        $form = $this->getWidgetForm($productPrice->getPriceList());
        $form['oro_action_operation[price][product]'] = $productPrice->getProduct()->getId();
        $form['oro_action_operation[price][quantity]'] = $productPrice->getQuantity();
        $form['oro_action_operation[price][unit]'] = $productPrice->getUnit()->getCode();
        $form['oro_action_operation[price][price][value]'] = $productPrice->getPrice()->getValue();
        $form['oro_action_operation[price][price][currency]'] = $productPrice->getPrice()->getCurrency();

        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains(
            'Product has duplication of product prices. ' .
            'Set of fields "PriceList", "Quantity" , "Unit" and "Currency" should be unique.',
            $crawler->html()
        );
    }

    /**
     * @param PriceList $priceList
     * @return Form
     */
    protected function getWidgetForm(PriceList $priceList)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_widget_form',
                [
                    'operationName' => 'orob2b_pricing_add_product_price',
                    'route' => 'orob2b_pricing_price_list_view',
                    'entityId' => $priceList->getId(),
                    'entityClass' => 'Oro\Bundle\PricingBundle\Entity\PriceList'
                ]
            ),
            ['_widgetContainer' => 'dialog']
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        return $crawler->selectButton('Save')->form();
    }
}
