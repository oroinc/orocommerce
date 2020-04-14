<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class CustomerTaxCodeControllerTest extends WebTestCase
{
    const ACCOUNT_TAX_CODE = 'unique';
    const ACCOUNT_TAX_CODE_UPDATED = 'uniqueUpdated';
    const ACCOUNT_TAX_CODE_DESCRIPTION = 'description';
    const ACCOUNT_TAX_CODE_DESCRIPTION_UPDATED = 'description updated';
    const ACCOUNT_TAX_CODE_SAVE_MESSAGE = 'Customer Tax Code has been saved';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('oro_tax_customer_tax_code_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_tax_customer_tax_code_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertCustomerTaxCodeSave($crawler, self::ACCOUNT_TAX_CODE, self::ACCOUNT_TAX_CODE_DESCRIPTION);
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'tax-customer-tax-codes-grid',
            ['tax-customer-tax-codes-grid[_filter][code][value]' => self::ACCOUNT_TAX_CODE]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id = $result['id'];
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_tax_customer_tax_code_update', ['id' => $result['id']])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertCustomerTaxCodeSave(
            $crawler,
            self::ACCOUNT_TAX_CODE_UPDATED,
            self::ACCOUNT_TAX_CODE_DESCRIPTION_UPDATED
        );

        return $id;
    }

    /**
     * @depends testUpdate
     * @param int $id
     */
    public function testView($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_tax_customer_tax_code_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        static::assertStringContainsString(
            self::ACCOUNT_TAX_CODE_UPDATED . ' - View - Customer Tax Codes - Taxes',
            $html
        );

        $this->assertViewPage(
            $html,
            self::ACCOUNT_TAX_CODE_UPDATED,
            self::ACCOUNT_TAX_CODE_DESCRIPTION_UPDATED
        );
    }

    /**
     * @param Crawler $crawler
     * @param string  $code
     * @param string  $description
     */
    protected function assertCustomerTaxCodeSave(Crawler $crawler, $code, $description)
    {
        $form = $crawler->selectButton('Save and Close')->form(
            [
                'oro_tax_customer_tax_code_type[code]' => $code,
                'oro_tax_customer_tax_code_type[description]' => $description,
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        static::assertStringContainsString(self::ACCOUNT_TAX_CODE_SAVE_MESSAGE, $html);
        $this->assertViewPage($html, $code, $description);
    }

    /**
     * @param string $html
     * @param string $code
     * @param string $description
     */
    protected function assertViewPage($html, $code, $description)
    {
        static::assertStringContainsString($code, $html);
        static::assertStringContainsString($description, $html);
    }
}
