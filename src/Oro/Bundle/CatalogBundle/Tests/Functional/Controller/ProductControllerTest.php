<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Controller;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryUnitPrecisionData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;

class ProductControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private const SIDEBAR_ROUTE = 'oro_catalog_category_product_sidebar';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadCategoryProductData::class, LoadCategoryUnitPrecisionData::class]);
    }

    /**
     * @dataProvider viewDataProvider
     */
    public function testView(bool $includeSubcategories, bool $includeNotCategorized, array $expected)
    {
        /** @var Category $secondLevelCategory */
        $secondLevelCategory = $this->getReference(LoadCategoryData::SECOND_LEVEL1);

        $response = $this->client->requestGrid(
            [
                'gridName' => 'products-grid',
                RequestProductHandler::CATEGORY_ID_KEY => $secondLevelCategory->getId(),
                RequestProductHandler::INCLUDE_SUBCATEGORIES_KEY => $includeSubcategories,
                RequestProductHandler::INCLUDE_NOT_CATEGORIZED_PRODUCTS_KEY => $includeNotCategorized,
            ],
            [],
            true
        );
        $result = $this->getJsonResponseContent($response, 200);
        $count = count($expected);
        $this->assertCount($count, $result['data']);
        foreach ($result['data'] as $data) {
            $this->assertContains($data['sku'], $expected);
        }
    }

    public function testViewWithoutCategoryAndWithNotCategorizedProduct()
    {
        $response = $this->client->requestGrid(
            [
                'gridName' => 'products-grid',
                RequestProductHandler::INCLUDE_NOT_CATEGORIZED_PRODUCTS_KEY => true,
            ],
            [],
            true
        );
        $result = $this->getJsonResponseContent($response, 200);
        $this->assertCount(1, $result['data']);

        foreach ($result['data'] as $data) {
            self::assertStringContainsString($data['sku'], LoadProductData::PRODUCT_9);
        }
    }

    public function viewDataProvider(): array
    {
        return [
            'includeSubcategories' => [
                'includeSubcategories' => true,
                'includeNotCategorized' => false,
                'expected' => [
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                ],
            ],
            'excludeSubcategories' => [
                'includeSubcategories' => false,
                'includeNotCategorized' => false,
                'expected' => [
                    LoadProductData::PRODUCT_2,
                ],
            ],
            'included subcategories and include not categorized products' => [
                'includeSubcategories' => true,
                'includeNotCategorized' => true,
                'expected' => [
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_9,
                ],
            ],
            'exclude subcategories and include not categorized products' => [
                'includeSubcategories' => false,
                'includeNotCategorized' => true,
                'expected' => [
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_9,
                ],
            ],
        ];
    }

    public function testSidebarAction()
    {
        $categoryId = 1;
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                self::SIDEBAR_ROUTE,
                [RequestProductHandler::CATEGORY_ID_KEY => $categoryId]
            ),
            ['_widgetContainer' => 'widget']
        );
        $json = $crawler->filterXPath('//*[@data-role="jstree-wrapper"]/*[@data-page-component-view]')
            ->attr('data-page-component-view');

        $this->assertJson($json);
        $arr = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($arr['defaultCategoryId'], $categoryId);
        $this->assertCount(8, $arr['data']);
    }

    /**
     * @dataProvider defaultUnitPrecisionDataProvider
     */
    public function testDefaultProductUnitPrecision(bool $singleUnitMode, ?string $category, string $expected)
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_product.single_unit_mode', $singleUnitMode);
        $configManager->flush();
        $systemDefaultUnit = $configManager->get('oro_product.default_unit');
        $systemDefaultPrecision = $configManager->get('oro_product.default_unit_precision');

        $categoryReference = $category ? $this->getReference($category) : null;

        $systemPrecision = [
            'unit' => $systemDefaultUnit,
            'precision' => $systemDefaultPrecision
        ];

        /** @var CategoryUnitPrecision $unitPrecision */
        $unitPrecision = $this->getReference(LoadCategoryData::SECOND_LEVEL1)
            ->getDefaultProductOptions()
            ->getUnitPrecision();
        $categoryPrecision = [
            'unit' => $unitPrecision->getUnit()->getCode(),
            'precision' => $unitPrecision->getPrecision()
        ];

        $expectedUnitPrecisions = [
            'systemPrecision' => $systemPrecision,
            'categoryPrecision' => $categoryPrecision,
        ];

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_create'));
        $form = $crawler->selectButton('Continue')->form();
        $formValues = $form->getPhpValues();
        $formValues['input_action'] = 'oro_product_create';
        if ($categoryReference) {
            $formValues['oro_product_step_one']['category'] = $categoryReference->getId();
            $formValues['oro_product_step_one']['type'] = Product::TYPE_SIMPLE;
        }

        $this->client->followRedirects(true);
        $crawler = $this->client->request(
            'POST',
            $this->getUrl('oro_product_create'),
            $formValues
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $form = $crawler->selectButton('Save and Close')->form();
        $this->assertDefaultProductUnit(
            $form,
            $expectedUnitPrecisions[$expected]['unit'],
            $expectedUnitPrecisions[$expected]['precision']
        );
    }

    public function defaultUnitPrecisionDataProvider(): array
    {
        return [
            'noCategory' => [
                'singleUnitMode' => false,
                'category' => null,
                'expectedData'  => 'systemPrecision'
            ],
            'CategoryWithPrecision' => [
                'singleUnitMode' => false,
                'category' => LoadCategoryData::SECOND_LEVEL1,
                'expectedData'  => 'categoryPrecision'
            ],
            'CategoryWithPrecisionButSingleUnitMode' => [
                'singleUnitMode' => true,
                'category' => LoadCategoryData::SECOND_LEVEL1,
                'expectedData'  => 'systemPrecision'
            ],
            'CategoryWithParentPrecision' => [
                'singleUnitMode' => false,
                'category' => LoadCategoryData::THIRD_LEVEL1,
                'expectedData'  => 'categoryPrecision'
            ],
            'CategoryWithNoPrecision' => [
                'singleUnitMode' => false,
                'category' => LoadCategoryData::FIRST_LEVEL,
                'expectedData'  => 'systemPrecision'
            ],
        ];
    }

    /**
     * Asserts that default product unit field is added and filled.
     */
    private function assertDefaultProductUnit(Form $form, string $unit, int $precision): void
    {
        $formValues = $form->getValues();

        $this->assertEquals(
            $unit,
            $formValues['oro_product[primaryUnitPrecision][unit]']
        );
        $this->assertEquals(
            $precision,
            $formValues['oro_product[primaryUnitPrecision][precision]']
        );
    }
}
