<?php

namespace OroB2B\Bundle\InvoiceBundle\Tests\Functional\Controller;

use OroB2B\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\AccountBundle\Entity\Account;

/**
 * @dbIsolation
 */
class InvoiceControllerTest extends WebTestCase
{

    const PO_NUMBER = '12';
    const PO_NUMBER_UPDATED = '18';

    /**
     * @var NameFormatter
     */
    protected $formatter;


    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader()));

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountAddresses',
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
            ]
        );

        $this->formatter = $this->getContainer()->get('oro_locale.formatter.name');
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_invoice_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals('Invoices', $crawler->filter('h1.oro-subtitle')->html());
    }

    /**
     * @return int
     */
    public function testCreate()
    {
        $today = (new \DateTime('now'))->format('Y-m-d');
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_invoice_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();
        $account = $this->getAccount();

        /** @var Product $product */
        $product = $this->getReference('product.1');

        $lineItems = [
            [
                'product' => $product->getId(),
                'quantity' => 10,
                'productUnit' => 'liter',
                'price' => [
                    'value' => 100,
                    'currency' => 'USD',
                ],
                'priceType' => PriceTypeAwareInterface::PRICE_TYPE_UNIT,
            ],
        ];
        $submittedData = [
            'input_action' => 'save_and_stay',
            'orob2b_invoice_type' => [
                '_token' => $form['orob2b_invoice_type[_token]']->getValue(),
                'owner' => $this->getCurrentUser()->getId(),
                'account' => $account->getId(),
                'poNumber' => self::PO_NUMBER,
                'invoiceDate' => $today,
                'paymentDueDate' => $today,
                'currency' => 'USD',
                'lineItems' => $lineItems,
            ],
        ];

        $this->client->followRedirects(true);

        // Submit form
        $result = $this->client->getResponse();
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $submittedData);
        $this->assertHtmlResponseStatusCodeEquals($result, 200);


        $this->assertEquals(
            self::PO_NUMBER,
            $crawler->filter('input[name="orob2b_invoice_type[poNumber]"]')->extract('value')[0]
        );

        $actualLineItems = $this->getActualLineItems($crawler, count($lineItems));

        $expectedLineItems = [
            [
                'product' => $product->getId(),
                'freeFormProduct' => '',
                'quantity' => 10,
                'productUnit' => 'orob2b.product_unit.liter.label.full',
                'price' => [
                    'value' => 100,
                    'currency' => 'USD',
                ],
                'priceType' => $this->getContainer()->get('translator')->trans('orob2b.pricing.price_type.unit'),
            ],
        ];

        $this->assertEquals($expectedLineItems, $actualLineItems);

        $response = $this->client->requestGrid(
            'invoices-grid',
            [
                'invoices-grid[_filter][poNumber][value]' => self::PO_NUMBER,
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        return $result['id'];
    }

    /**
     * @depends testCreate
     * @param int $id
     */
    public function testUpdateLineItems($id)
    {
        $today = (new \DateTime('now'))->format('Y-m-d');
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_invoice_update', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $account = $this->getAccount();

        /** @var Product $product */
        $product = $this->getReference('product.2');

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $lineItems = [
            [
                'product' => $product->getId(),
                'quantity' => 1,
                'productUnit' => 'bottle',
                'price' => [
                    'value' => 10,
                    'currency' => 'USD',
                ],
                'priceType' => PriceTypeAwareInterface::PRICE_TYPE_UNIT,
            ],
            [
                'freeFormProduct' => 'Free form product',
                'quantity' => 20,
                'productUnit' => 'liter',
                'price' => [
                    'value' => 200,
                    'currency' => 'USD',
                ],
                'priceType' => PriceTypeAwareInterface::PRICE_TYPE_BUNDLED,
            ],
        ];
        $submittedData = [
            'input_action' => 'save_and_stay',
            'orob2b_invoice_type' => [
                '_token' => $form['orob2b_invoice_type[_token]']->getValue(),
                'owner' => $this->getCurrentUser()->getId(),
                'account' => $account->getId(),
                'poNumber' => self::PO_NUMBER_UPDATED,
                'currency' => 'USD',
                'invoiceDate' => $today,
                'paymentDueDate' => $today,
                'lineItems' => $lineItems,
            ],
        ];

        $this->client->followRedirects(true);

        // Submit form
        $result = $this->client->getResponse();
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $submittedData);

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        // Check updated line items
        $actualLineItems = $this->getActualLineItems($crawler, count($lineItems));

        $expectedLineItems = [
            [
                'freeFormProduct' => '',
                'product' => $product->getId(),
                'quantity' => 1,
                'productUnit' => 'orob2b.product_unit.bottle.label.full',
                'price' => [
                    'value' => 10,
                    'currency' => 'USD',
                ],
                'priceType' => $this->getContainer()->get('translator')->trans('orob2b.pricing.price_type.unit'),
            ],
            [
                'product' => '',
                'freeFormProduct' => 'Free form product',
                'quantity' => 20,
                'productUnit' => 'orob2b.product_unit.liter.label.full',
                'price' => [
                    'value' => 200,
                    'currency' => 'USD',
                ],
                'priceType' => $this->getContainer()->get('translator')->trans('orob2b.pricing.price_type.bundled'),
            ],
        ];

        $this->assertEquals($expectedLineItems, $actualLineItems);
    }

    /**
     * @depends testCreate
     *
     * @param int $id
     */
    public function testView($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_invoice_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains(self::PO_NUMBER_UPDATED, $html);
    }

    /**
     * @return User
     */
    protected function getCurrentUser()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser();
    }

    /**
     * @param Crawler $crawler
     * @param int $count
     * @return array
     */
    protected function getActualLineItems(Crawler $crawler, $count)
    {
        $result = [];

        for ($i = 0; $i < $count; $i++) {
            $result[] = [
                'product' => $crawler->filter('input[name="orob2b_invoice_type[lineItems]['.$i.'][product]"]')
                    ->extract('value')[0],
                'freeFormProduct' => $crawler
                    ->filter('input[name="orob2b_invoice_type[lineItems]['.$i.'][freeFormProduct]"]')
                    ->extract('value')[0],
                'quantity' => $crawler->filter('input[name="orob2b_invoice_type[lineItems]['.$i.'][quantity]"]')
                    ->extract('value')[0],
                'productUnit' => $crawler
                    ->filter('select[name="orob2b_invoice_type[lineItems]['.$i.'][productUnit]"] :selected')
                    ->html(),
                'price' => [
                    'value' => $crawler->filter('input[name="orob2b_invoice_type[lineItems]['.$i.'][price][value]"]')
                        ->extract('value')[0],
                    'currency' => $crawler
                        ->filter('input[name="orob2b_invoice_type[lineItems]['.$i.'][price][currency]"]')
                        ->extract('value')[0],
                ],
                'priceType' => $crawler
                    ->filter('select[name="orob2b_invoice_type[lineItems]['.$i.'][priceType]"] :selected')
                    ->html(),
            ];
        }

        return $result;
    }

    /**
     * @return Account
     */
    protected function getAccount()
    {
        $doctrine = $this->getContainer()->get('doctrine');

        return $doctrine->getRepository('OroB2BAccountBundle:Account')->findOneBy([]);
    }
}
