<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Form\Type;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Entity\Manager\ProductManager;
use OroB2B\Bundle\ProductBundle\Autocomplete\ProductVisibilityLimitedSearchHandler;

/**
 * @dbIsolation
 */
abstract class AbstractProductSelectTypeTest extends WebTestCase
{
    const TEST_ENTITY_CLASS = 'OroB2B\Bundle\ProductBundle\Entity\Product';

    /** @var  Request|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var ProductManager|\PHPUnit_Framework_MockObject_MockObject $productManager */
    protected $productManager;

    /** @var array */
    protected $defaultConfigValue = ['in_stock', 'out_of_stock'];

    protected function setUp()
    {
        $this->initClient();

        $this->productManager = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Manager\ProductManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
            ]
        );

        $this->prepareConfig($this->defaultConfigValue);
    }

    protected function tearDown()
    {
        $this->prepareConfig($this->defaultConfigValue);
    }

    /**
     * @dataProvider searchDataProvider
     * @param array $availableInventoryStatuses
     * @param array $expectedProducts
     */
    public function testSearch(array $availableInventoryStatuses, array $expectedProducts)
    {
        $this->prepareConfig($availableInventoryStatuses);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_form_autocomplete_search',
                [
                    ProductSelectType::DATA_PARAMETERS => [
                        'scope' => $this->getScope(),
                    ],
                    'name' => 'orob2b_product_visibility_limited',
                    'page' => 1,
                    'per_page' => 10,
                    'query' => 'product',
                ]
            )
        );

        $result = $this->client->getResponse();
        $this->checkProductsFromResponse($result, 'results', $expectedProducts);
    }

    /**
     * @dataProvider searchDataProvider
     * @param array $availableInventoryStatuses
     * @param array $expectedProducts
     */
    public function testDatagridRestricting(array $availableInventoryStatuses, array $expectedProducts)
    {
        $this->prepareConfig($availableInventoryStatuses);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_datagrid_index',
                ['gridName' => 'products-select-grid']
            ),
            [
                ProductSelectType::DATA_PARAMETERS => [
                    'scope' => $this->getScope(),
                ],
            ]
        );
        $result = $this->client->getResponse();
        $this->checkProductsFromResponse($result, 'data', $expectedProducts);
    }

    /**
     * @return array
     */
    public function searchDataProvider()
    {
        return [
            [
                'availableInventoryStatuses' => ['in_stock', 'out_of_stock'],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                ],
            ],
            [
                'availableInventoryStatuses' => ['in_stock'],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                ],
            ],
            [
                'availableInventoryStatuses' => ['out_of_stock'],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_3,
                ],
            ],
            [
                'availableInventoryStatuses' => ['discontinued'],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_4,
                ],
            ],
            [
                'availableInventoryStatuses' => ['in_stock', 'discontinued'],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_4,
                ],
            ],
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Search handler is not fully configured
     */
    public function testCheckAllDependenciesInjectedException()
    {
        $requestStack = new RequestStack();

        $searchHandler = new ProductVisibilityLimitedSearchHandler(
            'OroB2B\Bundle\ProductBundle\Entity\Product',
            ['sku'],
            $requestStack,
            $this->productManager
        );
        $searchHandler->search('test', 1, 10);
    }

    /**
     * @param Request $request
     * @return \PHPUnit_Framework_MockObject_MockObject|RequestStack
     */
    protected function getRequestStack(Request $request)
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($request);

        return $requestStack;
    }

    /**
     * @param array $availableInventoryStatuses
     */
    protected function prepareConfig(array $availableInventoryStatuses)
    {
        $configManager = $this->getContainer()->get('oro_config.global');
        $configManager->set($this->getConfigPath(), $availableInventoryStatuses);

        $configManager->flush();
    }

    /**
     * @param Response $result
     * @param string $dataFieldName
     * @param string[] $expectedProducts
     * @return array
     */
    protected function checkProductsFromResponse(Response $result, $dataFieldName, array $expectedProducts)
    {
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $actualProducts = array_map(
            function ($result) {
                return $result->sku;
            },
            json_decode($result->getContent())->$dataFieldName
        );

        foreach ($actualProducts as $product) {
            $this->assertContains($product, $expectedProducts);
        }
    }

    /** @return string */
    abstract public function getConfigPath();

    /** @return string */
    abstract public function getScope();
}
