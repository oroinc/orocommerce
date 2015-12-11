<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

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
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices'
            ]
        );
    }

    public function testCreate()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.bottle');
        /** @var Product $product */
        $product = $this->getReference('product.1');

        $form = $this->getWidgetForm($priceList);
        $form['oro_action[price][product]'] = $product->getId();
        $form['oro_action[price][quantity]'] = 10;
        $form['oro_action[price][unit]'] = $unit->getCode();
        $form['oro_action[price][price][value]'] = 20;
        $form['oro_action[price][price][currency]'] = 'USD';

        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('widget.trigger(\'formSave\', []);', $crawler->html());
    }

    public function testCreateDuplicateEntry()
    {
        /** @var ProductPrice $productPrice */
        $productPrice = $this->getReference('product_price.3');

        $form = $this->getWidgetForm($productPrice->getPriceList());
        $form['oro_action[price][product]'] = $productPrice->getProduct()->getId();
        $form['oro_action[price][quantity]'] = $productPrice->getQuantity();
        $form['oro_action[price][unit]'] = $productPrice->getUnit()->getCode();
        $form['oro_action[price][price][value]'] = $productPrice->getPrice()->getValue();
        $form['oro_action[price][price][currency]'] = $productPrice->getPrice()->getCurrency();

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
                    'actionName' => 'orob2b_pricing_add_product_price_action',
                    'route' => 'orob2b_pricing_price_list_view',
                    'entityId' => $priceList->getId(),
                    'entityClass' => 'OroB2B\Bundle\PricingBundle\Entity\PriceList'
                ]
            )
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        return $crawler->selectButton('Save')->form();
    }
}
