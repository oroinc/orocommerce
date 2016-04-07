<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Form\Type;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;

/**
 * @dbIsolation
 */
class ProductPriceUnitSelectorTypeTest extends WebTestCase
{
    /**
     * @var array
     */
    protected $newPrice = [
        'quantity' => 1,
        'unit'     => 'box',
        'price'    => '12.34',
        'currency' => 'EUR',
    ];

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices']);
    }

    public function testNewPriceWithNewUnit()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_product_update', ['id' => $product->getId()])
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $formValues = $form->getPhpValues();
        $formValues['orob2b_product']['unitPrecisions'][] = [
            'unit' => $this->newPrice['unit'],
            'precision' => 0
        ];
        $formValues['orob2b_product']['prices'][] = [
            'priceList' => $priceList->getId(),
            'quantity' => $this->newPrice['quantity'],
            'unit' => $this->newPrice['unit'],
            'price' => [
                'value' => $this->newPrice['price'],
                'currency' => $this->newPrice['currency'],
            ]
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Product has been saved', $crawler->html());

        /** @var ProductPrice $price */
        $price = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:ProductPrice')
            ->getRepository('OroB2BPricingBundle:ProductPrice')
            ->findOneBy([
                'product' => $product,
                'priceList' => $priceList,
                'quantity' => $this->newPrice['quantity'],
                'unit' => $this->newPrice['unit'],
                'currency' => $this->newPrice['currency'],
            ]);
        $this->assertNotEmpty($price);
        $this->assertEquals($this->newPrice['price'], $price->getPrice()->getValue());
    }
}
