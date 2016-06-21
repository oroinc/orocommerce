<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;
use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;

/**
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
{
    const TEST_SKU = 'SKU-001';
    const STATUS = 'Disabled';
    const INVENTORY_STATUS = 'In Stock';
    const DEFAULT_NAME = 'default name';
    const DEFAULT_DESCRIPTION = 'default description';
    const FIRST_UNIT_CODE = 'item';
    const FIRST_UNIT_PRECISION = '0';

    const CATEGORY_ID = 1;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes',
            ]
        );
    }

    public function testCreate()
    {
        $productTaxCode = $this->getReference(
            LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_1
        );

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_create'));
        $form = $crawler->selectButton('Continue')->form();
        $formValues = $form->getPhpValues();
        $formValues['input_action'] = 'orob2b_product_create';
        $formValues['orob2b_product_step_one']['category'] = self::CATEGORY_ID;

        $this->client->followRedirects(true);
        $crawler = $this->client->request('POST', $this->getUrl('orob2b_product_create'), $formValues);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();
        $formValues['orob2b_product']['sku'] = self::TEST_SKU;
        $formValues['orob2b_product']['owner'] = $this->getBusinessUnitId();
        $formValues['orob2b_product']['inventoryStatus'] = Product::INVENTORY_STATUS_IN_STOCK;
        $formValues['orob2b_product']['status'] = Product::STATUS_DISABLED;
        $formValues['orob2b_product']['names']['values']['default'] = self::DEFAULT_NAME;
        $formValues['orob2b_product']['descriptions']['values']['default'] = self::DEFAULT_DESCRIPTION;
        $formValues['orob2b_product']['taxCode'] = $productTaxCode->getId();
        $formValues['orob2b_product']['primaryUnitPrecision'] = [
            'unit' => self::FIRST_UNIT_CODE,
            'precision' => self::FIRST_UNIT_PRECISION,
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains('Product has been saved', $html);
        $this->assertContains(self::TEST_SKU, $html);
        $this->assertContains(self::INVENTORY_STATUS, $html);
        $this->assertContains(self::STATUS, $html);
        $this->assertContains($productTaxCode->getCode(), $html);
    }

    /**
     * @depends testCreate
     */
    public function testView()
    {
        $productTaxCode = $this->getReference(
            LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_1
        );

        $product = $this->getProductDataBySku(self::TEST_SKU);
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_view', ['id' => $product->getId()]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains($productTaxCode->getCode(), $html);
    }

    /**
     * @depends testView
     */
    public function testTaxCodeViewContainsEntity()
    {
        /** @var ProductTaxCode $productTaxCode */
        $productTaxCode = $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_1);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_tax_product_tax_code_view', ['id' => $productTaxCode->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $grid = $crawler->filter('.inner-grid')->eq(0)->attr('data-page-component-options');
        $this->assertContains(self::TEST_SKU, $grid);
    }

    /**
     * @depends testTaxCodeViewContainsEntity
     */
    public function testGrid()
    {
        /** @var ProductTaxCode $productTaxCode */
        $productTaxCode = $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_1);

        $response = $this->client->requestGrid(
            'products-grid',
            ['products-grid[_filter][sku][value]' => self::TEST_SKU]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $this->assertArrayHasKey('taxCode', $result);
        $this->assertEquals($productTaxCode->getCode(), $result['taxCode']);
    }

    /**
     * @return int
     */
    protected function getBusinessUnitId()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser()->getOwner()->getId();
    }

    /**
     * @param string $sku
     * @return Product
     */
    private function getProductDataBySku($sku)
    {
        /** @var Product $product */
        $product = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BProductBundle:Product')
            ->getRepository('OroB2BProductBundle:Product')
            ->findOneBy(['sku' => $sku]);
        $this->assertNotEmpty($product);

        return $product;
    }
}
