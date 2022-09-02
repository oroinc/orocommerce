<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Fallback;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\EntityBundle\Tests\Functional\Helper\FallbackTestTrait;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

class LowInventoryThresholdFallbackTest extends WebTestCase
{
    use FallbackTestTrait;

    const VIEW_INVENTORY_THRESHOLD_XPATH =
    "//label[text() = 'Low Inventory Threshold']/following-sibling::div/div[contains(@class,  'control-label')]";

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadCategoryProductData::class]);
    }

    public function testProductLowInventoryThresholdView()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $product->getId()]));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $inventoryThresholdValue = $crawler->filterXPath(self::VIEW_INVENTORY_THRESHOLD_XPATH)->html();
        $this->assertEquals(0, $inventoryThresholdValue);
    }

    public function testProductLowInventoryThresholdUpdate()
    {
        $newValue = 2.5;
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $crawler = $this->setProductLowInventoryThresholdField($product, $newValue, null);
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $value = $crawler->filterXPath(self::VIEW_INVENTORY_THRESHOLD_XPATH)->html();
        $this->assertEquals($newValue, $value);
    }

    public function testCategoryLowInventoryThreshold()
    {
        $newCategoryFallbackValue = 3.5;
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_catalog_category_update', ['id' => $category->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();
        $inventoryThresholdValue = $form->get('oro_catalog_category[lowInventoryThreshold][scalarValue]')->getValue();
        $this->assertEmpty($inventoryThresholdValue);

        $form['input_action'] = $crawler->selectButton('Save')->attr('data-action');
        $form['oro_catalog_category[lowInventoryThreshold][useFallback]'] = false;
        $form['oro_catalog_category[lowInventoryThreshold][scalarValue]'] = $newCategoryFallbackValue;
        $this->client->followRedirects(true);

        $crawler = $this->client->submit($form);

        $form = $crawler->selectButton('Save')->form();
        $this->assertEquals(
            $newCategoryFallbackValue,
            $form->get('oro_catalog_category[lowInventoryThreshold][scalarValue]')->getValue()
        );
    }

    /**
     * @param Product $product
     * @param mixed   $ownValue
     * @param mixed   $fallbackValue
     *
     * @return Crawler
     */
    protected function setProductLowInventoryThresholdField($product, $ownValue, $fallbackValue)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $product->getId()]));

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['input_action'] = $crawler->selectButton('Save and Close')->attr('data-action');

        $this->updateFallbackField(
            $form,
            $ownValue,
            $fallbackValue,
            'oro_product',
            LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION
        );

        $this->client->followRedirects(true);

        return $this->client->submit($form);
    }
}
