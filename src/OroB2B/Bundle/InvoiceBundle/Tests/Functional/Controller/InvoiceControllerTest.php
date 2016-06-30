<?php

namespace OroB2B\Bundle\InvoiceBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

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

    /**
     * @param Form $form
     * @param Account $account
     * @param string $today
     * @param array $lineItems
     * @param string $poNumber
     * @return array
     */
    public function getSubmittedData($form, $account, $today, $lineItems, $poNumber)
    {
        return [
            '_token' => $form['orob2b_invoice_type[_token]']->getValue(),
            'owner' => $this->getCurrentUser()->getId(),
            'account' => $account->getId(),
            'poNumber' => $poNumber,
            'invoiceDate' => $today,
            'paymentDueDate' => $today,
            'currency' => 'USD',
            'lineItems' => $lineItems,
        ];
    }

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader()));

        $this->loadFixtures(
            [
                'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
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
        $this->assertContains('invoices-grid', $crawler->html());
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
                'sortOrder' => 1,
            ],
        ];
        $submittedData = [
            'input_action' => 'save_and_stay',
            'orob2b_invoice_type' => $this->getSubmittedData($form, $account, $today, $lineItems, self::PO_NUMBER),
        ];

        $this->client->followRedirects(true);

        // Submit form
        $result = $this->client->getResponse();
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $submittedData);
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

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
                'sortOrder' => 1,
            ],
        ];

        $this->assertEquals($expectedLineItems, $actualLineItems);

        $invoice = $this->fetchInvoice(['poNumber' => self::PO_NUMBER]);

        $this->assertSame('1000.0000', $invoice->getSubtotal());
        $this->assertSame(self::PO_NUMBER, $invoice->getPoNumber());

        return $invoice->getId();
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
                'sortOrder' => '1',
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
                'sortOrder' => '2',
            ],
        ];
        $submittedData = [
            'input_action' => 'save_and_stay',
            'orob2b_invoice_type' => $this->getSubmittedData(
                $form,
                $account,
                $today,
                $lineItems,
                self::PO_NUMBER_UPDATED
            ),
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
                'sortOrder' => 1,
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
                'sortOrder' => 2,
            ],
        ];

        $invoice = $this->fetchInvoice(['id' => $id]);
        $this->assertEquals($expectedLineItems, $actualLineItems);
        $this->assertSame('210.0000', $invoice->getSubtotal());
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
     * @param array $criteria
     * @return Invoice
     */
    protected function fetchInvoice(array $criteria)
    {
        return $this->client->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepository('OroB2B\Bundle\InvoiceBundle\Entity\Invoice')
            ->findOneBy($criteria);
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
                    'value' => $crawler
                        ->filter('input[name="orob2b_invoice_type[lineItems]['.$i.'][price][value]"]')
                        ->extract('value')[0],
                    'currency' => $crawler
                        ->filter('input[name="orob2b_invoice_type[lineItems]['.$i.'][price][currency]"]')
                        ->extract('value')[0],
                ],
                'priceType' => $crawler
                    ->filter('select[name="orob2b_invoice_type[lineItems]['.$i.'][priceType]"] :selected')
                    ->html(),
                'sortOrder' => $crawler
                    ->filter('input[name="orob2b_invoice_type[lineItems]['.$i.'][sortOrder]"]')
                    ->extract('value')[0],
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
