<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Handler\RequestProductHandler;
use OroB2B\Bundle\CatalogBundle\Model\CategoryUnitPrecision;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\FrontendBundle\Test\Client;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
{
    const SIDEBAR_ROUTE = 'orob2b_catalog_category_product_sidebar';

    /**
     * @var Client
     */
    protected $client;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryUnitPrecisionData'
        ]);
    }

    /**
     * @dataProvider viewDataProvider
     *
     * @param bool $includeSubcategories
     * @param array $expected
     */
    public function testView($includeSubcategories, $expected)
    {
        /** @var Category $secondLevelCategory */
        $secondLevelCategory = $this->getReference(LoadCategoryData::SECOND_LEVEL1);

        $response = $this->client->requestGrid(
            [
                'gridName' => 'products-grid',
                RequestProductHandler::CATEGORY_ID_KEY => $secondLevelCategory->getId(),
                RequestProductHandler::INCLUDE_SUBCATEGORIES_KEY => $includeSubcategories,
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

    /**
     * @return array
     */
    public function viewDataProvider()
    {
        return [
            'includeSubcategories' => [
                'includeSubcategories' => true,
                'expected' => [
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                ],
            ],
            'excludeSubcategories' => [
                'includeSubcategories' => false,
                'expected' => [
                    LoadProductData::PRODUCT_2,
                ],
            ],
        ];
    }

    public function testSidebarAction()
    {
        $categoryId = 2;
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                static::SIDEBAR_ROUTE,
                [RequestProductHandler::CATEGORY_ID_KEY => $categoryId]
            ),
            ['_widgetContainer' => 'widget']
        );
        $json = $crawler->filterXPath('//*[@data-page-component-options]')->attr('data-page-component-options');
        $this->assertJson($json);
        $arr = json_decode($json, true);
        $this->assertEquals($arr['defaultCategoryId'], $categoryId);
        $this->assertCount(8, $arr['data']);
    }

    /**
     * @dataProvider defaultUnitPrecisionDataProvider
     *
     * @param string $category
     * @param string $expected
     */
    public function testDefaultProductUnitPrecision($category, $expected)
    {
        $configManager = $this->client->getContainer()->get('oro_config.manager');
        $systemDefaultUnit = $configManager->get('orob2b_product.default_unit');
        $systemDefaultPrecision = $configManager->get('orob2b_product.default_unit_precision');

        $reflectionClass =
            new \ReflectionClass('OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData');
        $constName = $reflectionClass->getConstant($category);
        $categoryReference = $constName ? $this->getReference($constName) : null;

        $systemPrecision = [
            'unit' => $systemDefaultUnit,
            'precision' =>$systemDefaultPrecision
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

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_create'));
        $form = $crawler->selectButton('Continue')->form();
        $formValues = $form->getPhpValues();
        $formValues['input_action'] = 'orob2b_product_create';
        if ($categoryReference) {
            $formValues['orob2b_product_step_one']['category'] = $categoryReference->getId();
        }

        $this->client->followRedirects(true);
        $crawler = $this->client->request(
            'POST',
            $this->getUrl('orob2b_product_create'),
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

    /**
     * @return array
     */
    public function defaultUnitPrecisionDataProvider()
    {
        return [
            'noCategory' => [
                'category' => null,
                'expectedData'  => 'systemPrecision'
            ],
            'CategoryWithPrecision' => [
                'category' => 'SECOND_LEVEL1',
                'expectedData'  => 'categoryPrecision'
            ],
            'CategoryWithParentPrecision' => [
                'category' => 'THIRD_LEVEL1',
                'expectedData'  => 'categoryPrecision'
            ],
            'CategoryWithNoPrecision' => [
                'category' => 'FIRST_LEVEL',
                'expectedData'  => 'systemPrecision'
            ],
        ];
    }

    /**
     * checking if default product unit field is added and filled
     *
     * @param Form $form
     * @param string $unit
     * @param integer $precision
     */
    protected function assertDefaultProductUnit($form, $unit, $precision)
    {
        $formValues = $form->getValues();

        $this->assertEquals(
            $unit,
            $formValues['orob2b_product[primaryUnitPrecision][unit]']
        );
        $this->assertEquals(
            $precision,
            $formValues['orob2b_product[primaryUnitPrecision][precision]']
        );
    }
}
