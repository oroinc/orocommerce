<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Autocomplete;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Manager\ProductManager;
use OroB2B\Bundle\ProductBundle\Autocomplete\ProductVisibilityLimitedSearchHandler;

/**
 * @dbIsolation
 */
class ProductVisibilityLimitedSearchHandlerTest extends WebTestCase
{
    const TEST_ENTITY_CLASS = 'OroB2B\Bundle\ProductBundle\Entity\Product';

    /** @var  Request|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var ProductManager|\PHPUnit_Framework_MockObject_MockObject $productManager */
    protected $productManager;

    /** @var array  */
    protected $defaultConfigValue = ['in_stock', 'out_of_stock'];

    protected function setUp()
    {
        $this->initClient();

        $this->productManager = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->loadFixtures([
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadProductData'
        ]);

        $this->prepareConfig($this->defaultConfigValue);
    }

    protected function tearDown()
    {
        $this->prepareConfig($this->defaultConfigValue);
    }

    /**
     * @dataProvider searchDataProvider
     * @param array $dataParameters
     * @param array $availableInventoryStatuses
     * @param array $expectedProducts
     */
    public function testSearch(array $dataParameters, array $availableInventoryStatuses, array $expectedProducts)
    {
        $this->prepareConfig($availableInventoryStatuses);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_form_autocomplete_search',
                [
                    'data_parameters' => $dataParameters,
                    'name' => 'orob2b_product_visibility_limited',
                    'page' => 1,
                    'per_page' => 10,
                    'query' => 'product'
                ]
            )
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $this->assertEquals($expectedProducts, $this->getProductsFromResponse($result));
    }

    /**
     * @return array
     */
    public function searchDataProvider()
    {
        return [
            [
                'dataParameters' => [
                    'scope' => 'order'
                ],
                'availableInventoryStatuses' => ['in_stock', 'out_of_stock' ],
                'expectedProducts' => [
                    'test_product_01',
                    'test_product_02',
                    'test_product_03',
                ]
            ],
            [
                'dataParameters' => [
                    'scope' => 'order'
                ],
                'availableInventoryStatuses' => ['in_stock'],
                'expectedProducts' => [
                    'test_product_01',
                    'test_product_02',
                ]
            ],
            [
                'dataParameters' => [
                    'scope' => 'order'
                ],
                'availableInventoryStatuses' => ['out_of_stock'],
                'expectedProducts' => [
                    'test_product_03',
                ]
            ],
            [
                'dataParameters' => [
                    'scope' => 'order'
                ],
                'availableInventoryStatuses' => ['discontinued'],
                'expectedProducts' => [
                    'test_product_04',
                ]
            ],
            [
                'dataParameters' => [
                    'scope' => 'order'
                ],
                'availableInventoryStatuses' => ['in_stock', 'discontinued'],
                'expectedProducts' => [
                    'test_product_01',
                    'test_product_02',
                    'test_product_04',
                ]
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
        $configManager->set('oro_b2b_order.product_visibility.value', $availableInventoryStatuses);

        $configManager->flush();
    }

    /**
     * @param Response $result
     * @return array
     */
    protected function getProductsFromResponse(Response $result)
    {
        return array_map(
            function ($result) {
                return $result->sku;
            },
            json_decode($result->getContent())->results
        );
    }
}
