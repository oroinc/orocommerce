<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Controller;

use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes;
use OroB2B\Bundle\ProductBundle\Entity\Product;

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

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_product[sku]'] = self::TEST_SKU;
        $form['orob2b_product[owner]'] = $this->getBusinessUnitId();

        $form['orob2b_product[inventoryStatus]'] = Product::INVENTORY_STATUS_IN_STOCK;
        $form['orob2b_product[status]'] = Product::STATUS_DISABLED;
        $form['orob2b_product[names][values][default]'] = self::DEFAULT_NAME;
        $form['orob2b_product[descriptions][values][default]'] = self::DEFAULT_DESCRIPTION;
        $form['orob2b_product[taxCode]'] = $productTaxCode->getId();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

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

        $result = $this->getProductDataBySku(self::TEST_SKU);
        $id = (int)$result['id'];

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_view', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains($productTaxCode->getCode(), $html);
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
     * @return array
     */
    private function getProductDataBySku($sku)
    {
        $response = $this->client->requestGrid(
            'products-grid',
            ['products-grid[_filter][sku][value]' => $sku]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('data', $result);
        $this->assertNotEmpty($result['data']);

        $result = reset($result['data']);
        $this->assertNotEmpty($result);

        return $result;
    }
}
