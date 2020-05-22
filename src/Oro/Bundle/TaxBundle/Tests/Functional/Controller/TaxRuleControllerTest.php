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
    const TAX_DESCRIPTION = 'description';
    const TAX_DESCRIPTION_UPDATED = 'description updated';

    const TAX_RULE_SAVE_MESSAGE = 'Tax Rule has been saved';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                'Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadCustomerTaxCodes',
                'Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes',
                'Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxes',
                'Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxJurisdictions',
            ]
        );
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_tax_rule_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('tax-taxe-rules-grid', $crawler->html());
    }

    public function testCreate()
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
            ->getManagerForClass('OroTaxBundle:TaxRule')
            ->getRepository('OroTaxBundle:TaxRule')
            ->findOneBy(['description' => self::TAX_DESCRIPTION]);
        $this->assertNotEmpty($taxRule);

        return $taxRule->getId();
    }

    /**
     * @param string $reference
     * @return CustomerTaxCode
     */
    protected function getCustomerTaxCode($reference)
    {
        return $this->getReference(LoadCustomerTaxCodes::REFERENCE_PREFIX . '.' . $reference);
    }

    /**
     * @param string $reference
     * @return ProductTaxCode
     */
    protected function getProductTaxCode($reference)
    {
        return $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX . '.' . $reference);
    }

    /**
     * @param string $reference
     * @return Tax
     */
    protected function getTax($reference)
    {
        return $this->getReference(LoadTaxes::REFERENCE_PREFIX . '.' . $reference);
    }

    /**
     * @param string $reference
     * @return TaxJurisdiction
     */
    protected function getTaxJurisdiction($reference)
    {
        return $this->getReference(LoadTaxJurisdictions::REFERENCE_PREFIX . '.' . $reference);
    }

    /**
     * @param $id int
     * @return int
     * @depends testCreate
     */
    public function testUpdate($id)
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
     * @param int $id
     */
    public function testView($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_tax_rule_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        static::assertStringContainsString($id . ' - View - Tax Rules - Taxes', $html);

        $this->assertViewPage(
            $html,
            $this->getCustomerTaxCode(LoadCustomerTaxCodes::TAX_2),
            $this->getProductTaxCode(LoadProductTaxCodes::TAX_2),
            $this->getTax(LoadTaxes::TAX_2),
            $this->getTaxJurisdiction(LoadTaxes::TAX_2),
            self::TAX_DESCRIPTION_UPDATED
        );
    }

    /**
     * @param Crawler         $crawler
     * @param CustomerTaxCode  $customerTaxCode
     * @param ProductTaxCode  $productTaxCode
     * @param Tax             $tax
     * @param TaxJurisdiction $taxJurisdiction
     * @param string          $description
     */
    protected function assertTaxRuleSave(
        Crawler $crawler,
        CustomerTaxCode $customerTaxCode,
        ProductTaxCode $productTaxCode,
        Tax $tax,
        TaxJurisdiction $taxJurisdiction,
        $description
    ) {
        $form = $crawler->selectButton('Save and Close')->form(
            [
                'oro_tax_rule_type[description]' => $description,
                'oro_tax_rule_type[customerTaxCode]' => $customerTaxCode->getId(),
                'oro_tax_rule_type[productTaxCode]' => $productTaxCode->getId(),
                'oro_tax_rule_type[tax]' => $tax->getId(),
                'oro_tax_rule_type[taxJurisdiction]' => $taxJurisdiction->getId(),
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        static::assertStringContainsString(self::TAX_RULE_SAVE_MESSAGE, $html);
        $this->assertViewPage($html, $customerTaxCode, $productTaxCode, $tax, $taxJurisdiction, $description);
    }

    /**
     * @param string          $html
     * @param CustomerTaxCode  $customerTaxCode
     * @param ProductTaxCode  $productTaxCode
     * @param Tax             $tax
     * @param TaxJurisdiction $taxJurisdiction
     * @param string          $description
     */
    protected function assertViewPage(
        $html,
        CustomerTaxCode $customerTaxCode,
        ProductTaxCode $productTaxCode,
        Tax $tax,
        TaxJurisdiction $taxJurisdiction,
        $description
    ) {
        static::assertStringContainsString($description, $html);
        static::assertStringContainsString($customerTaxCode->getCode(), $html);
        static::assertStringContainsString($productTaxCode->getCode(), $html);
        static::assertStringContainsString($tax->getCode(), $html);
        static::assertStringContainsString($taxJurisdiction->getCode(), $html);
    }
}
