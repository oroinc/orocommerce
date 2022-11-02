<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Controller;

use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadCustomerTaxCodes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxJurisdictions;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class TaxRuleControllerTest extends WebTestCase
{
    private const TAX_DESCRIPTION = 'description';
    private const TAX_DESCRIPTION_UPDATED = 'description updated';
    private const TAX_RULE_SAVE_MESSAGE = 'Tax Rule has been saved';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadCustomerTaxCodes::class,
            LoadProductTaxCodes::class,
            LoadTaxes::class,
            LoadTaxJurisdictions::class,
        ]);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_tax_rule_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('tax-taxe-rules-grid', $crawler->html());
    }

    public function testCreate(): int
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_tax_rule_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertTaxRuleSave(
            $crawler,
            $this->getCustomerTaxCode(LoadCustomerTaxCodes::TAX_1),
            $this->getProductTaxCode(LoadProductTaxCodes::TAX_1),
            $this->getTax(LoadTaxes::TAX_1),
            $this->getTaxJurisdiction(LoadTaxes::TAX_1),
            self::TAX_DESCRIPTION
        );

        /** @var TaxRule $taxRule */
        $taxRule = $this->getContainer()->get('doctrine')
            ->getRepository(TaxRule::class)
            ->findOneBy(['description' => self::TAX_DESCRIPTION]);
        $this->assertNotEmpty($taxRule);

        return $taxRule->getId();
    }

    private function getCustomerTaxCode(string $reference): CustomerTaxCode
    {
        return $this->getReference(LoadCustomerTaxCodes::REFERENCE_PREFIX . '.' . $reference);
    }

    private function getProductTaxCode(string $reference): ProductTaxCode
    {
        return $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX . '.' . $reference);
    }

    private function getTax(string $reference): Tax
    {
        return $this->getReference(LoadTaxes::REFERENCE_PREFIX . '.' . $reference);
    }

    private function getTaxJurisdiction(string $reference): TaxJurisdiction
    {
        return $this->getReference(LoadTaxJurisdictions::REFERENCE_PREFIX . '.' . $reference);
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(int $id): int
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_tax_rule_update', ['id' => $id])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertTaxRuleSave(
            $crawler,
            $this->getCustomerTaxCode(LoadCustomerTaxCodes::TAX_2),
            $this->getProductTaxCode(LoadProductTaxCodes::TAX_2),
            $this->getTax(LoadTaxes::TAX_2),
            $this->getTaxJurisdiction(LoadTaxes::TAX_2),
            self::TAX_DESCRIPTION_UPDATED
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
            $this->getUrl('oro_tax_rule_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        self::assertStringContainsString($id . ' - View - Tax Rules - Taxes', $html);

        $this->assertViewPage(
            $html,
            $this->getCustomerTaxCode(LoadCustomerTaxCodes::TAX_2),
            $this->getProductTaxCode(LoadProductTaxCodes::TAX_2),
            $this->getTax(LoadTaxes::TAX_2),
            $this->getTaxJurisdiction(LoadTaxes::TAX_2),
            self::TAX_DESCRIPTION_UPDATED
        );
    }

    private function assertTaxRuleSave(
        Crawler $crawler,
        CustomerTaxCode $customerTaxCode,
        ProductTaxCode $productTaxCode,
        Tax $tax,
        TaxJurisdiction $taxJurisdiction,
        string $description
    ): void {
        $form = $crawler->selectButton('Save and Close')->form(
            [
                'oro_tax_rule_type[description]' => $description,
                'oro_tax_rule_type[customerTaxCode]' => $customerTaxCode->getId(),
                'oro_tax_rule_type[productTaxCode]' => $productTaxCode->getId(),
                'oro_tax_rule_type[tax]' => $tax->getId(),
                'oro_tax_rule_type[taxJurisdiction]' => $taxJurisdiction->getId(),
            ]
        );
        $redirectAction = $crawler->selectButton('Save and Close')->attr('data-action');
        $form->setValues(['input_action' => $redirectAction]);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        self::assertStringContainsString(self::TAX_RULE_SAVE_MESSAGE, $html);
        $this->assertViewPage($html, $customerTaxCode, $productTaxCode, $tax, $taxJurisdiction, $description);
    }

    private function assertViewPage(
        string $html,
        CustomerTaxCode $customerTaxCode,
        ProductTaxCode $productTaxCode,
        Tax $tax,
        TaxJurisdiction $taxJurisdiction,
        string $description
    ): void {
        self::assertStringContainsString($description, $html);
        self::assertStringContainsString($customerTaxCode->getCode(), $html);
        self::assertStringContainsString($productTaxCode->getCode(), $html);
        self::assertStringContainsString($tax->getCode(), $html);
        self::assertStringContainsString($taxJurisdiction->getCode(), $html);
    }
}
