<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Fallback;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

abstract class InventoryFallbackTest extends WebTestCase
{
    const VIEW_MANAGED_INVENTORY_XPATH =
        "//label[text() = 'Highlight Low Inventory']/following-sibling::div/div[contains(@class,  'control-label')]";

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures($this->getFixtures());
    }

    /**
     * @return array
     */
    protected function getFixtures()
    {
        return [LoadCategoryProductData::class];
    }

    /**
     * @depends testProductCategorySystemFallback
     */
    public function testProductNoHighlightLowInventoryValueFallbacksToSystemDefault()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $product->getId()]));
        $manageInventoryValue = $crawler->filterXPath(static::VIEW_MANAGED_INVENTORY_XPATH)->html();
        $this->assertEquals('Yes', $manageInventoryValue);
    }

    /**
     * @param mixed  $systemValue
     * @param string $expectedProductValue
     * @param bool   $updateProduct
     * @param bool   $updateCategory
     */
    abstract public function testProductCategorySystemFallback(
        $systemValue,
        $expectedProductValue,
        $updateProduct = false,
        $updateCategory = false
    );

    /**
     * @param mixed $ownValue
     * @param bool $useFallbackValue
     * @param mixed $fallbackValue
     * @param string $expectedValue
     *
     * @dataProvider productWithNoFallbackProvider
     */
    public function testProductWithNoFallback($ownValue, $useFallbackValue, $fallbackValue, $expectedValue)
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $crawler = $this->setProductInventoryField($product, $ownValue, $useFallbackValue, $fallbackValue);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertProductInventoryValue($crawler, $expectedValue);
    }

    /**
     * @return array
     */
    public function productWithNoFallbackProvider()
    {
        return [
            ['1', false, null, 'Yes'],
            ['0', false, null, 'No'],
        ];
    }

    /**
     * @param string $categoryOwnValue
     * @param bool $categoryUseFallbackValue
     * @param mixed $categoryFallbackValue
     * @param string $expectedProductValue
     * @param bool $updateProduct
     *
     * @dataProvider productCategoryFallbackProvider
     */
    public function testProductCategoryFallback(
        $categoryOwnValue,
        $categoryUseFallbackValue,
        $categoryFallbackValue,
        $expectedProductValue,
        $updateProduct = false
    ) {
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        if ($updateProduct) {
            $this->setProductInventoryField($product, null, true, 'category');
        }

        $this->setCategoryInventoryField(
            $categoryOwnValue,
            $categoryUseFallbackValue,
            $categoryFallbackValue
        );

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $product->getId()]));
        $this->assertProductInventoryValue($crawler, $expectedProductValue);
    }

    /**
     * @return array
     */
    public function productCategoryFallbackProvider()
    {
        return [
            ['1', false, null, 'Yes', true],
            ['0', false, null, 'No'],
        ];
    }

    /**
     * @return array
     */
    public function productCategorySystemFallbackProvider()
    {
        return [
            [0, 'No', true, true],
            [1, 'Yes'],
        ];
    }

    /**
     * @param mixed $ownValue
     * @param bool $useFallbackValue
     * @param mixed $fallbackValue
     *
     * @return null|Crawler
     */
    protected function setCategoryInventoryField($ownValue, $useFallbackValue, $fallbackValue)
    {
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_catalog_category_update', ['id' => $category->getId()])
        );

        $form = $crawler->selectButton('Save')->form();
        $formValues = $form->getPhpValues();

        $highlightLowInventoryOption = LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION;
        $lowInventoryThresholdOption = LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION;
        if (is_null($ownValue)) {
            unset($formValues['oro_catalog_category']['manageInventory']['scalarValue']);
            unset($formValues['oro_catalog_category'][$highlightLowInventoryOption]['scalarValue']);
        } else {
            $formValues['oro_catalog_category']['manageInventory']['scalarValue'] = $ownValue;
            $formValues['oro_catalog_category'][$highlightLowInventoryOption]['scalarValue'] = $ownValue;
        }

        if (!is_null($useFallbackValue)) {
            $formValues['oro_catalog_category']['manageInventory']['useFallback'] = $useFallbackValue;
            $formValues['oro_catalog_category'][$highlightLowInventoryOption]['useFallback'] = $useFallbackValue;
        }
        if (is_null($fallbackValue)) {
            unset($formValues['oro_catalog_category']['manageInventory']['fallback']);
            unset($formValues['oro_catalog_category'][$highlightLowInventoryOption]['fallback']);
        } else {
            $formValues['oro_catalog_category']['manageInventory']['fallback'] = $fallbackValue;
            $formValues['oro_catalog_category'][$highlightLowInventoryOption]['fallback'] = $fallbackValue;
        }

        $formValues['oro_catalog_category']['inventoryThreshold']['useFallback'] = '1';
        $formValues['oro_catalog_category'][$lowInventoryThresholdOption]['useFallback'] = '1';
        $formValues['oro_catalog_category']['_token'] =
            $this->getContainer()->get('security.csrf.token_manager')->getToken('category')->getValue();

        $this->client->followRedirects(true);

        return $this->client->request($form->getMethod(), $form->getUri(), $formValues);
    }

    /**
     * @param Product $product
     * @param mixed $ownValue
     * @param bool $useFallbackValue
     * @param mixed $fallbackValue
     *
     * @return Crawler
     */
    protected function setProductInventoryField($product, $ownValue, $useFallbackValue, $fallbackValue)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $product->getId()]));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['input_action'] = 'save_and_close';
        if (is_null($ownValue)) {
            unset($form['oro_product[manageInventory][scalarValue]']);
            unset($form['oro_product[highlightLowInventory][scalarValue]']);
        } else {
            $form['oro_product[manageInventory][scalarValue]'] = $ownValue;
            $form['oro_product[highlightLowInventory][scalarValue]'] = $ownValue;
        }
        if (!is_null($useFallbackValue)) {
            $form['oro_product[manageInventory][useFallback]'] = $useFallbackValue;
            $form['oro_product[highlightLowInventory][useFallback]'] = $useFallbackValue;
        }
        if (is_null($fallbackValue)) {
            unset($form['oro_product[manageInventory][fallback]']);
            unset($form['oro_product[highlightLowInventory][fallback]']);
        } else {
            $form['oro_product[manageInventory][fallback]'] = $fallbackValue;
            $form['oro_product[highlightLowInventory][fallback]'] = $fallbackValue;
        }

        $this->client->followRedirects(true);

        return $this->client->submit($form);
    }

    /**
     * @param Crawler $crawler
     * @param string $expectedValue
     *
     * @return string
     */
    protected function assertProductInventoryValue(Crawler $crawler, $expectedValue)
    {
        $value = $crawler->filterXPath(static::VIEW_MANAGED_INVENTORY_XPATH)->html();
        $this->assertEquals($expectedValue, $value);

        return $value;
    }
}
