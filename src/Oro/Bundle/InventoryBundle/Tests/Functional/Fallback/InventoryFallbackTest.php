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
    protected const VIEW_MANAGED_INVENTORY_XPATH =
        "//label[text() = 'Highlight Low Inventory']/following-sibling::div/div[contains(@class,  'control-label')]";

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures($this->getFixtures());
    }

    protected function getFixtures(): array
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

    abstract public function testProductCategorySystemFallback(
        mixed $systemValue,
        string $expectedProductValue,
        bool $updateProduct = false,
        bool $updateCategory = false
    );

    /**
     * @dataProvider productWithNoFallbackProvider
     */
    public function testProductWithNoFallback(
        mixed $ownValue,
        bool $useFallbackValue,
        mixed $fallbackValue,
        string $expectedValue
    ) {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $crawler = $this->setProductInventoryField($product, $ownValue, $useFallbackValue, $fallbackValue);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertProductInventoryValue($crawler, $expectedValue);
    }

    public function productWithNoFallbackProvider(): array
    {
        return [
            ['1', false, null, 'Yes'],
            ['0', false, null, 'No'],
        ];
    }

    /**
     * @dataProvider productCategoryFallbackProvider
     */
    public function testProductCategoryFallback(
        string $categoryOwnValue,
        bool $categoryUseFallbackValue,
        mixed $categoryFallbackValue,
        string $expectedProductValue,
        bool $updateProduct = false
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

    public function productCategoryFallbackProvider(): array
    {
        return [
            ['1', false, null, 'Yes', true],
            ['0', false, null, 'No'],
        ];
    }

    public function productCategorySystemFallbackProvider(): array
    {
        return [
            [0, 'No', true, true],
            [1, 'Yes'],
        ];
    }

    protected function setCategoryInventoryField(
        mixed $ownValue,
        ?bool $useFallbackValue,
        mixed $fallbackValue
    ): ?Crawler {
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_catalog_category_update', ['id' => $category->getId()])
        );

        $form = $crawler->selectButton('Save')->form();
        $formValues = $form->getPhpValues();

        $highlightLowInventoryOption = LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION;
        $lowInventoryThresholdOption = LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION;
        if (null === $ownValue) {
            unset(
                $formValues['oro_catalog_category']['manageInventory']['scalarValue'],
                $formValues['oro_catalog_category'][$highlightLowInventoryOption]['scalarValue']
            );
        } else {
            $formValues['oro_catalog_category']['manageInventory']['scalarValue'] = $ownValue;
            $formValues['oro_catalog_category'][$highlightLowInventoryOption]['scalarValue'] = $ownValue;
        }

        if (null !== $useFallbackValue) {
            $formValues['oro_catalog_category']['manageInventory']['useFallback'] = $useFallbackValue;
            $formValues['oro_catalog_category'][$highlightLowInventoryOption]['useFallback'] = $useFallbackValue;
        }
        if (null === $fallbackValue) {
            unset(
                $formValues['oro_catalog_category']['manageInventory']['fallback'],
                $formValues['oro_catalog_category'][$highlightLowInventoryOption]['fallback']
            );
        } else {
            $formValues['oro_catalog_category']['manageInventory']['fallback'] = $fallbackValue;
            $formValues['oro_catalog_category'][$highlightLowInventoryOption]['fallback'] = $fallbackValue;
        }

        $formValues['oro_catalog_category']['inventoryThreshold']['useFallback'] = '1';
        $formValues['oro_catalog_category'][$lowInventoryThresholdOption]['useFallback'] = '1';
        $formValues['oro_catalog_category']['_token'] = $this->getCsrfToken('category')->getValue();

        $this->client->followRedirects(true);

        return $this->client->request($form->getMethod(), $form->getUri(), $formValues);
    }

    protected function setProductInventoryField(
        Product $product,
        mixed $ownValue,
        ?bool $useFallbackValue,
        mixed $fallbackValue
    ): ?Crawler {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $product->getId()]));

        $form = $crawler->selectButton('Save and Close')->form();
        $formValues = $form->getPhpValues();

        $highlightLowInventoryOption = LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION;

        $formValues['input_action'] = $crawler->selectButton('Save and Close')->attr('data-action');
        if (null === $ownValue) {
            unset(
                $formValues['oro_product']['manageInventory']['scalarValue'],
                $formValues['oro_product'][$highlightLowInventoryOption]['scalarValue']
            );
        } else {
            $formValues['oro_product']['manageInventory']['scalarValue'] = $ownValue;
            $formValues['oro_product'][$highlightLowInventoryOption]['scalarValue'] = $ownValue;
        }
        if (null !== $useFallbackValue) {
            $formValues['oro_product']['manageInventory']['useFallback'] = $useFallbackValue;
            $formValues['oro_product'][$highlightLowInventoryOption]['useFallback'] = $useFallbackValue;
        }
        if (null === $fallbackValue) {
            unset(
                $formValues['oro_product']['manageInventory']['fallback'],
                $formValues['oro_product'][$highlightLowInventoryOption]['fallback']
            );
        } else {
            $formValues['oro_product']['manageInventory']['fallback'] = $fallbackValue;
            $formValues['oro_product'][$highlightLowInventoryOption]['fallback'] = $fallbackValue;
        }

        $formValues['oro_product']['_token'] = $this->getCsrfToken('product')->getValue();

        $this->client->followRedirects(true);

        return $this->client->request($form->getMethod(), $form->getUri(), $formValues);
    }

    protected function assertProductInventoryValue(Crawler $crawler, string $expectedValue): string
    {
        $value = $crawler->filterXPath(static::VIEW_MANAGED_INVENTORY_XPATH)->html();
        $this->assertEquals($expectedValue, $value);

        return $value;
    }
}
