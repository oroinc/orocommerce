<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Fallback;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\EntityBundle\Tests\Functional\Helper\FallbackTestTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class InventoryThresholdFallbackTest extends WebTestCase
{
    use FallbackTestTrait;

    private const VIEW_INVENTORY_THRESHOLD_XPATH =
        "//label[text() = 'Inventory Threshold']/following-sibling::div/div[contains(@class,  'control-label')]";

    #[\Override]
    protected function setUp(): void
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
        $newValue = 2.5;
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $crawler = $this->setProductInventoryThresholdField($product, $newValue, null);
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $value = $crawler->filterXPath(self::VIEW_INVENTORY_THRESHOLD_XPATH)->html();
        $this->assertEquals($newValue, $value);
    }

    public function testCategoryInventoryThreshold()
    {
        // Get form
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_catalog_category_update', ['id' => $category->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $form = $crawler->selectButton('Save')->form();

        // Ensure that the tested field has a value different from the value that it will be changed to
        $inventoryThresholdValue = $form->get('oro_catalog_category[inventoryThreshold][scalarValue]')->getValue();
        $this->assertEquals('', $inventoryThresholdValue);

        // Fill the form
        $this->updateFallbackField($form, '3.5', null, 'oro_catalog_category', 'inventoryThreshold');
        unset($form['oro_catalog_category[inventoryThreshold][useFallback]']);

        // Submit form
        $this->client->followRedirects();
        $crawler = $this->client->submit($form);

        // Assert result
        $form = $crawler->selectButton('Save')->form();
        $actualScalarValue = $form->get('oro_catalog_category[inventoryThreshold][scalarValue]')->getValue();
        $this->assertEquals('3.5', $actualScalarValue);

        // Ensure that the flash message was fired
        $this->assertStringContainsString('Category has been saved', $this->client->getResponse()->getContent());
    }

    private function setProductInventoryThresholdField(
        Product $product,
        mixed $ownValue,
        mixed $fallbackValue
    ): Crawler {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $product->getId()]));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['input_action'] = $crawler->selectButton('Save and Close')->attr('data-action');

        $this->updateFallbackField($form, $ownValue, $fallbackValue, 'oro_product', 'inventoryThreshold');

        $this->client->followRedirects();

        return $this->client->submit($form);
    }
}
