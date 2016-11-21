<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Fallback;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class InventoryThresholdFallbackTest extends WebTestCase
{
    const VIEW_INVENTORY_THRESHOLD_XPATH =
    "//label[text() = 'Inventory Threshold']/following-sibling::div/div[contains(@class,  'control-label')]";

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadCategoryProductData::class]);
    }

    public function testProductInventoryThresholdView()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $product->getId()]));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $inventoryThresholdValue = $crawler->filterXPath(self::VIEW_INVENTORY_THRESHOLD_XPATH)->html();
        $this->assertEquals(0, $inventoryThresholdValue);
    }

    public function testProductInventoryThresholdUpdate()
    {
        $newValue = 5;
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $crawler = $this->setProductInventoryThresholdField($product, $newValue, false, null);
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $value = $crawler->filterXPath(self::VIEW_INVENTORY_THRESHOLD_XPATH)->html();
        $this->assertEquals($newValue, $value);
    }

    public function testCategoryInventoryThreshold()
    {
        $newCategoryFallbackValue = 5;
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_catalog_category_update', ['id' => $category->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $form = $crawler->selectButton('Save')->form();
        $inventoryThresholdValue = $form->get('oro_catalog_category[inventoryThreshold][scalarValue]')->getValue();
        $this->assertEmpty($inventoryThresholdValue);

        $form['input_action'] = 'save';
        $form['oro_catalog_category[inventoryThreshold][useFallback]'] = false;
        $form['oro_catalog_category[inventoryThreshold][scalarValue]'] = $newCategoryFallbackValue;
        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form);

        $form = $crawler->selectButton('Save')->form();
        $this->assertEquals(
            $newCategoryFallbackValue,
            $form->get('oro_catalog_category[inventoryThreshold][scalarValue]')->getValue()
        );
    }

    /**
     * @param Product $product
     * @param mixed $ownValue
     * @param bool $useFallbackValue
     * @param mixed $fallbackValue
     * @return Crawler
     */
    protected function setProductInventoryThresholdField($product, $ownValue, $useFallbackValue, $fallbackValue)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $product->getId()]));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['input_action'] = 'save_and_close';
        if (is_null($ownValue)) {
            unset($form['oro_product[inventoryThreshold][scalarValue]']);
        } else {
            $form['oro_product[inventoryThreshold][scalarValue]'] = $ownValue;
        }
        if (!is_null($useFallbackValue)) {
            $form['oro_product[inventoryThreshold][useFallback]'] = $useFallbackValue;
        }
        if (is_null($fallbackValue)) {
            unset($form['oro_product[inventoryThreshold][fallback]']);
        } else {
            $form['oro_product[inventoryThreshold][fallback]'] = $fallbackValue;
        }

        $this->client->followRedirects(true);

        return $this->client->submit($form);
    }
}