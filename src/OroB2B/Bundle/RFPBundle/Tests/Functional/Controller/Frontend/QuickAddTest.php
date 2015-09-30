<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Component\Testing\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Model\DataStorageAwareProcessor;

/**
 * @dbIsolation
 */
class QuickAddTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
            ]
        );
    }

    public function testQuickAdd()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_frontend_quick_add'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->filter('form[name="orob2b_product_quick_add"]')->form();

        /** @var Product $product */
        $product = $this->getReference('product.3');

        $products = [
            [
                'productSku' => $product->getSku(),
                'productQuantity' => 15,
            ],
        ];

        /** @var DataStorageAwareProcessor $processor */
        $processor = $this->getContainer()->get('orob2b_rfp.processor.quick_add');

        $this->client->followRedirects(true);
        $crawler = $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            [
                'orob2b_product_quick_add' => [
                    '_token' => $form['orob2b_product_quick_add[_token]']->getValue(),
                    'products' => $products,
                    'component' => $processor->getName(),
                ],
            ]
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $expectedQuickAddLineItems = [
            [
                'product' => $product->getId(),
                'quantity' => 15,
            ],
        ];

        $this->assertEquals($expectedQuickAddLineItems, $this->getActualLineItems($crawler, 1));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_rfp_frontend_request[firstName]'] = 'Firstname';
        $form['orob2b_rfp_frontend_request[lastName]'] = 'Lastname';
        $form['orob2b_rfp_frontend_request[email]'] = 'email@example.com';
        $form['orob2b_rfp_frontend_request[phone]'] = '55555555';
        $form['orob2b_rfp_frontend_request[company]'] = 'Test Company';
        $form['orob2b_rfp_frontend_request[role]'] = 'Test Role';
        $form['orob2b_rfp_frontend_request[body]'] = 'Test Body';
        $form['orob2b_rfp_frontend_request[requestProducts][0][requestProductItems][0][price][value]'] = 100;
        $form['orob2b_rfp_frontend_request[requestProducts][0][requestProductItems][0][price][currency]'] = 'USD';

        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('Request has been saved', $crawler->html());
    }

    /**
     * @param Crawler $crawler
     * @param int $count
     * @return array
     */
    protected function getActualLineItems(Crawler $crawler, $count)
    {
        $result = [];
        $basePath = 'input[name="orob2b_rfp_frontend_request[requestProducts]';

        for ($i = 0; $i < $count; $i++) {
            $value = $crawler->filter($basePath.'['.$i.'][product]"]')->extract('value');
            $quantity = $crawler->filter($basePath.'['.$i.'][requestProductItems][0][quantity]"]')
                ->extract('value');

            $this->assertNotEmpty($value, 'Product is empty');
            $this->assertNotEmpty($quantity, 'Quantity is empty');

            $result[] = ['product' => $value[0], 'quantity' => $quantity[0]];
        }

        return $result;
    }
}
