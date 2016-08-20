<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Form\Type;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Autocomplete\ProductVisibilityLimitedSearchHandler;

/**
 * @dbIsolation
 */
abstract class AbstractProductSelectTypeTest extends WebTestCase
{
    /** @var string */
    protected $searchAutocompletePath = 'oro_form_autocomplete_search';

    /** @var string */
    protected $datagridIndexPath = 'oro_datagrid_index';

    /** @var array */
    protected $dataParameters = [];

    public function setUp()
    {
        $this->initClient();
    }

    /**
     * @dataProvider restrictionDataProvider
     * @param array $restrictionParams
     * @param array $expectedProducts
     */
    public function testSearchRestriction(array $restrictionParams, array $expectedProducts)
    {
        call_user_func_array([$this, 'setUpBeforeRestriction'], $restrictionParams);

        $this->client->request(
            'GET',
            $this->getUrl(
                $this->searchAutocompletePath,
                [
                    ProductSelectType::DATA_PARAMETERS => $this->dataParameters,
                    'name' => 'orob2b_product_visibility_limited',
                    'page' => 1,
                    'per_page' => 10,
                    'query' => 'product',
                ]
            )
        );

        $result = $this->client->getResponse();
        $this->assertResponseContainsProducts($result, 'results', $expectedProducts);
    }

    /**
     * @dataProvider restrictionDataProvider
     * @param array $restrictionParams
     * @param array $expectedProducts
     */
    public function testDatagridRestriction(array $restrictionParams, array $expectedProducts)
    {
        call_user_func_array([$this, 'setUpBeforeRestriction'], $restrictionParams);

        $this->client->request(
            'GET',
            $this->getUrl(
                $this->datagridIndexPath,
                ['gridName' => 'products-select-grid']
            ),
            [
                ProductSelectType::DATA_PARAMETERS => $this->dataParameters,
            ]
        );
        $result = $this->client->getResponse();
        $this->assertResponseContainsProducts($result, 'data', $expectedProducts);
    }

    public function setUpBeforeRestriction()
    {
    }

    /**
     * @return array
     */
    abstract public function restrictionDataProvider();

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Search handler is not fully configured
     */
    public function testAllDependenciesInjectedException()
    {
        $requestStack = new RequestStack();

        /** @var ProductManager|\PHPUnit_Framework_MockObject_MockObject $productManager */
        $productManager = $this->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Manager\ProductManager')
            ->disableOriginalConstructor()
            ->getMock();

        $searchHandler = new ProductVisibilityLimitedSearchHandler(
            'Oro\Bundle\ProductBundle\Entity\Product',
            ['sku'],
            $requestStack,
            $productManager
        );
        $searchHandler->search('test', 1, 10);
    }

    /**
     * @param Response $result
     * @param string $dataFieldName
     * @param string[] $expectedProducts
     * @return array
     */
    protected function assertResponseContainsProducts(Response $result, $dataFieldName, array $expectedProducts)
    {
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $actualProducts = array_map(
            function ($result) {
                return $result->sku;
            },
            json_decode($result->getContent())->$dataFieldName
        );

        $this->assertEquals(count($expectedProducts), count($actualProducts));

        foreach ($actualProducts as $product) {
            $this->assertContains($product, $expectedProducts);
        }
    }

    /**
     * @param string $datagridIndexPath
     */
    public function setDatagridIndexPath($datagridIndexPath)
    {
        $this->datagridIndexPath = $datagridIndexPath;
    }

    /**
     * @param string $searchAutocompletePath
     */
    public function setSearchAutocompletePath($searchAutocompletePath)
    {
        $this->searchAutocompletePath = $searchAutocompletePath;
    }

    /**
     * @param array $dataParameters
     */
    public function setDataParameters($dataParameters)
    {
        $this->dataParameters = $dataParameters;
    }
}
