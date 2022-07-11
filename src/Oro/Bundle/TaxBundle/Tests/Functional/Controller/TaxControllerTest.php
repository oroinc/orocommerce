<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Controller;

use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class TaxControllerTest extends WebTestCase
{
    private const TAX_CODE = 'unique';
    private const TAX_CODE_UPDATED = 'uniqueUpdated';
    private const TAX_DESCRIPTION = 'description';
    private const TAX_DESCRIPTION_UPDATED = 'description updated';
    private const TAX_RATE = 1;
    private const TAX_RATE_UPDATED = 2;
    private const TAX_SAVE_MESSAGE = 'Tax has been saved';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_tax_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('tax-taxes-grid', $crawler->html());
    }

    public function testCreate(): int
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_tax_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertTaxSave($crawler, self::TAX_CODE, self::TAX_DESCRIPTION, self::TAX_RATE);

        /** @var Tax $tax */
        $tax = $this->getContainer()->get('doctrine')
            ->getRepository(Tax::class)
            ->findOneBy(['code' => self::TAX_CODE]);
        $this->assertNotEmpty($tax);

        return $tax->getId();
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(int $id): int
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_tax_update', ['id' => $id])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertTaxSave($crawler, self::TAX_CODE_UPDATED, self::TAX_DESCRIPTION_UPDATED, self::TAX_RATE_UPDATED);

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testView(int $id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_tax_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        self::assertStringContainsString(self::TAX_CODE_UPDATED . ' - View - Taxes - Taxes', $html);

        $this->assertViewPage($html, self::TAX_CODE_UPDATED, self::TAX_DESCRIPTION_UPDATED, self::TAX_RATE_UPDATED);
    }

    private function assertTaxSave(Crawler $crawler, string $code, string $description, string $rate): void
    {
        $form = $crawler->selectButton('Save and Close')->form(
            [
                'oro_tax_type[code]' => $code,
                'oro_tax_type[description]' => $description,
                'oro_tax_type[rate]' => $rate,
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        self::assertStringContainsString(self::TAX_SAVE_MESSAGE, $html);
        $this->assertViewPage($html, $code, $description, $rate);
    }

    private function assertViewPage(string $html, string $code, string $description, string $rate): void
    {
        self::assertStringContainsString($code, $html);
        self::assertStringContainsString($description, $html);
        self::assertStringContainsString($rate . '%', $html);
    }
}
