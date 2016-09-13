<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Functional\Fallback;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class CategoryFallbackTest extends WebTestCase
{
    const VIEW_MANAGED_INVENTORY_XPATH =
        "//label[text() = 'Managed Inventory']/following-sibling::div/div[contains(@class,  'control-label')]";

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadCategoryProductData::class]);
    }

    public function testProductNoManageInventoryValue()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_view', ['id' => $product->getId()]));
        $manageInventoryValue = $crawler->filterXPath(self::VIEW_MANAGED_INVENTORY_XPATH)->html();
        $this->assertEquals('N/A', $manageInventoryValue);
    }

    /**
     * @param mixed $viewValue
     * @param bool $useFallbackValue
     * @param mixed $fallbackValue
     * @param string $expectedValue
     *
     * @dataProvider productWithNoFallbackProvider
     */
    public function testProductWithNoFallback($viewValue, $useFallbackValue, $fallbackValue, $expectedValue)
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $crawler = $this->setProductManageInventoryField($product, $viewValue, $useFallbackValue, $fallbackValue);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertProductManageInventoryValue($crawler, $expectedValue);
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
     * @param string $categoryViewValue
     * @param bool $categoryUseFallbackValue
     * @param mixed $categoryFallbackValue
     * @param string $expectedProductValue
     * @param bool $updateProduct
     *
     * @dataProvider productCategoryFallbackProvider
     */
    public function testProductCategoryFallback(
        $categoryViewValue,
        $categoryUseFallbackValue,
        $categoryFallbackValue,
        $expectedProductValue,
        $updateProduct = false
    ) {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        if ($updateProduct) {
            $this->setProductManageInventoryField($product, null, true, 'category');
        }
        $this->setCategoryManageInventoryField($categoryViewValue, $categoryUseFallbackValue, $categoryFallbackValue);

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_view', ['id' => $product->getId()]));
        $this->assertProductManageInventoryValue($crawler, $expectedProductValue);
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
     * @param mixed $systemValue
     * @param string $expectedProductValue
     * @param bool $updateProduct
     * @param bool $updateCategory
     *
     * @dataProvider productCategorySystemFallbackProvider
     */
    public function testProductCategorySystemFallback(
        $systemValue,
        $expectedProductValue,
        $updateProduct = false,
        $updateCategory = false
    ) {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        if ($updateProduct) {
            $this->setProductManageInventoryField($product, null, true, 'category');
        }
        if ($updateCategory) {
            $this->setCategoryManageInventoryField(null, true, 'systemConfig');
        }

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_config_configuration_system',
                ['activeGroup' => 'commerce', 'activeSubGroup' => 'product_options']
            )
        );
        $form = $crawler->selectButton('Save settings')->form();
        $formValues = $form->getPhpValues();
        $formValues['product_options']['orob2b_product___manage_inventory']['use_parent_scope_value'] = false;
        $formValues['product_options']['orob2b_product___manage_inventory']['value'] = $systemValue;
        $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_view', ['id' => $product->getId()]));
        $this->assertProductManageInventoryValue($crawler, $expectedProductValue);
    }

    /**
     * @return array
     */
    public function productCategorySystemFallbackProvider()
    {
        return [
            [false, 'No', true, true],
            [true, 'Yes'],
        ];
    }

    /**
     * @param mixed $viewValue
     * @param bool $useFallbackValue
     * @param mixed $fallbackValue
     * @return null|Crawler
     */
    protected function setCategoryManageInventoryField($viewValue, $useFallbackValue, $fallbackValue)
    {
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_catalog_category_update', ['id' => $category->getId()])
        );

        $form = $crawler->selectButton('Save')->form();
        $formValues = $form->getPhpValues();
        if (!is_null($viewValue)) {
            $formValues['orob2b_catalog_category']['manageInventory']['viewValue'] = $viewValue;
        }

        if (!is_null($useFallbackValue)) {
            $formValues['orob2b_catalog_category']['manageInventory']['useFallback'] = $useFallbackValue;
        }
        if (!is_null($fallbackValue)) {
            $formValues['orob2b_catalog_category']['manageInventory']['fallback'] = $fallbackValue;
        }

        $formValues['orob2b_catalog_category']['_token'] =
            $this->getContainer()->get('security.csrf.token_manager')->getToken('category');

        $this->client->followRedirects(true);

        return $this->client->request($form->getMethod(), $form->getUri(), $formValues);
    }

    /**
     * @param Product $product
     * @param mixed $viewValue
     * @param bool $useFallbackValue
     * @param mixed $fallbackValue
     * @return Crawler
     */
    protected function setProductManageInventoryField($product, $viewValue, $useFallbackValue, $fallbackValue)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_update', ['id' => $product->getId()]));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['input_action'] = 'save_and_close';
        if (!is_null($viewValue)) {
            $form['orob2b_product[manageInventory][viewValue]'] = $viewValue;
        }
        if (!is_null($useFallbackValue)) {
            $form['orob2b_product[manageInventory][useFallback]'] = $useFallbackValue;
        }
        if ($fallbackValue) {
            $form['orob2b_product[manageInventory][fallback]'] = $fallbackValue;
        }

        $this->client->followRedirects(true);

        return $this->client->submit($form);
    }

    /**
     * @param Crawler $crawler
     * @param string $expectedValue
     * @return string
     */
    protected function assertProductManageInventoryValue(Crawler $crawler, $expectedValue)
    {
        $value = $crawler->filterXPath(self::VIEW_MANAGED_INVENTORY_XPATH)->html();
        $this->assertEquals($expectedValue, $value);

        return $value;
    }
}
