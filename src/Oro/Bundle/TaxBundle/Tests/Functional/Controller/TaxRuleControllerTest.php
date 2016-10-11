<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Entity\AccountTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxJurisdictions;

/**
 * @dbIsolation
 */
class TaxRulesControllerTest extends WebTestCase
{
    const TAX_DESCRIPTION = 'description';
    const TAX_DESCRIPTION_UPDATED = 'description updated';

    const TAX_RULE_SAVE_MESSAGE = 'Tax Rule has been saved';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                'Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes',
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
        $this->assertContains('tax-taxe-rules-grid', $crawler->html());
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_tax_rule_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertTaxRuleSave(
            $crawler,
            $this->getAccountTaxCode(LoadAccountTaxCodes::TAX_1),
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
     * @return AccountTaxCode
     */
    protected function getAccountTaxCode($reference)
    {
        return $this->getReference(LoadAccountTaxCodes::REFERENCE_PREFIX . '.' . $reference);
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
            $this->getAccountTaxCode(LoadAccountTaxCodes::TAX_2),
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

        $this->assertContains($id . ' - View - Tax Rules - Taxes', $html);

        $this->assertViewPage(
            $html,
            $this->getAccountTaxCode(LoadAccountTaxCodes::TAX_2),
            $this->getProductTaxCode(LoadProductTaxCodes::TAX_2),
            $this->getTax(LoadTaxes::TAX_2),
            $this->getTaxJurisdiction(LoadTaxes::TAX_2),
            self::TAX_DESCRIPTION_UPDATED
        );
    }

    /**
     * @param Crawler         $crawler
     * @param AccountTaxCode  $accountTaxCode
     * @param ProductTaxCode  $productTaxCode
     * @param Tax             $tax
     * @param TaxJurisdiction $taxJurisdiction
     * @param string          $description
     */
    protected function assertTaxRuleSave(
        Crawler $crawler,
        AccountTaxCode $accountTaxCode,
        ProductTaxCode $productTaxCode,
        Tax $tax,
        TaxJurisdiction $taxJurisdiction,
        $description
    ) {
        $form = $crawler->selectButton('Save and Close')->form(
            [
                'oro_tax_rule_type[description]' => $description,
                'oro_tax_rule_type[accountTaxCode]' => $accountTaxCode->getId(),
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

        $this->assertContains(self::TAX_RULE_SAVE_MESSAGE, $html);
        $this->assertViewPage($html, $accountTaxCode, $productTaxCode, $tax, $taxJurisdiction, $description);
    }

    /**
     * @param string          $html
     * @param AccountTaxCode  $accountTaxCode
     * @param ProductTaxCode  $productTaxCode
     * @param Tax             $tax
     * @param TaxJurisdiction $taxJurisdiction
     * @param string          $description
     */
    protected function assertViewPage(
        $html,
        AccountTaxCode $accountTaxCode,
        ProductTaxCode $productTaxCode,
        Tax $tax,
        TaxJurisdiction $taxJurisdiction,
        $description
    ) {
        $this->assertContains($description, $html);
        $this->assertContains($accountTaxCode->getCode(), $html);
        $this->assertContains($productTaxCode->getCode(), $html);
        $this->assertContains($tax->getCode(), $html);
        $this->assertContains($taxJurisdiction->getCode(), $html);
    }
}
