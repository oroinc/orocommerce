<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Controller;

use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class ProductTaxCodeControllerTest extends WebTestCase
{
    private const PRODUCT_TAX_CODE = 'unique';
    private const PRODUCT_TAX_CODE_UPDATED = 'uniqueUpdated';
    private const PRODUCT_TAX_CODE_DESCRIPTION = 'description';
    private const PRODUCT_TAX_CODE_DESCRIPTION_UPDATED = 'description updated';
    private const PRODUCT_TAX_CODE_SAVE_MESSAGE = 'Product Tax Code has been saved';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_tax_product_tax_code_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('tax-product-tax-codes-grid', $crawler->html());
    }

    public function testCreate(): int
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_tax_product_tax_code_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertProductTaxCodeSave($crawler, self::PRODUCT_TAX_CODE, self::PRODUCT_TAX_CODE_DESCRIPTION);

        /** @var ProductTaxCode $taxCode */
        $taxCode = $this->getContainer()->get('doctrine')
            ->getRepository(ProductTaxCode::class)
            ->findOneBy(['code' => self::PRODUCT_TAX_CODE]);
        $this->assertNotEmpty($taxCode);

        return $taxCode->getId();
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(int $id): int
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_tax_product_tax_code_update', ['id' => $id])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertProductTaxCodeSave(
            $crawler,
            self::PRODUCT_TAX_CODE_UPDATED,
            self::PRODUCT_TAX_CODE_DESCRIPTION_UPDATED
        );

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testView(int $id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_tax_product_tax_code_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        self::assertStringContainsString(
            self::PRODUCT_TAX_CODE_UPDATED . ' - View - Product Tax Codes - Taxes',
            $html
        );

        $this->assertViewPage(
            $html,
            self::PRODUCT_TAX_CODE_UPDATED,
            self::PRODUCT_TAX_CODE_DESCRIPTION_UPDATED
        );
    }

    private function assertProductTaxCodeSave(Crawler $crawler, string $code, string $description): void
    {
        $form = $crawler->selectButton('Save and Close')->form(
            [
                'oro_tax_product_tax_code_type[code]' => $code,
                'oro_tax_product_tax_code_type[description]' => $description,
            ]
        );
        $redirectAction = $crawler->selectButton('Save and Close')->attr('data-action');
        $form->setValues(['input_action' => $redirectAction]);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        self::assertStringContainsString(self::PRODUCT_TAX_CODE_SAVE_MESSAGE, $html);
        $this->assertViewPage($html, $code, $description);
    }

    private function assertViewPage(string $html, string $code, string $description): void
    {
        self::assertStringContainsString($code, $html);
        self::assertStringContainsString($description, $html);
    }
}
