<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Form\Type;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Autocomplete\ProductVisibilityLimitedSearchHandler;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Search\ProductRepository as ProductSearchRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractProductSelectTypeTest extends WebTestCase
{
    /** @var string */
    protected $searchAutocompletePath = 'oro_form_autocomplete_search';

    /** @var string */
    protected $datagridIndexPath = 'oro_datagrid_index';

    /** @var string */
    protected $datagridName = 'products-select-grid';

    /** @var array */
    protected $dataParameters = [];

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
    }

    /**
     * @dataProvider restrictionSelectDataProvider
     */
    public function testSearchRestriction(array $restrictionParams, array $expectedProducts)
    {
        call_user_func_array([$this, 'setUpBeforeRestriction'], array_values($restrictionParams));

        $this->client->request(
            'GET',
            $this->getUrl(
                $this->searchAutocompletePath,
                [
                    ProductSelectType::DATA_PARAMETERS => $this->dataParameters,
                    'name' => 'oro_product_visibility_limited',
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
     * @dataProvider restrictionGridDataProvider
     */
    public function testDatagridRestriction(array $restrictionParams, array $expectedProducts)
    {
        call_user_func_array([$this, 'setUpBeforeRestriction'], array_values($restrictionParams));

        $this->client->request(
            'GET',
            $this->getUrl(
                $this->datagridIndexPath,
                ['gridName' => $this->datagridName]
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

    abstract public function restrictionSelectDataProvider(): array;

    abstract public function restrictionGridDataProvider(): array;

    public function testAllDependenciesInjectedException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Search handler is not fully configured');

        $searchHandler = new ProductVisibilityLimitedSearchHandler(
            Product::class,
            new RequestStack(),
            $this->createMock(ProductManager::class),
            $this->createMock(ProductSearchRepository::class),
            $this->createMock(LocalizationHelper::class),
            $this->createMock(FrontendHelper::class)
        );
        $searchHandler->search('test', 1, 10);
    }

    /**
     * @param Response $result
     * @param string   $dataFieldName
     * @param string[] $expectedProducts
     */
    protected function assertResponseContainsProducts(
        Response $result,
        string $dataFieldName,
        array $expectedProducts
    ): void {
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $actualProducts = array_map(
            function ($result) {
                return $result->sku;
            },
            json_decode($result->getContent())->$dataFieldName
        );

        $this->assertCount(count($expectedProducts), $actualProducts);

        foreach ($actualProducts as $product) {
            $this->assertContains($product, $expectedProducts);
        }
    }

    public function setDatagridName(string $datagridName): void
    {
        $this->datagridName = $datagridName;
    }

    public function setDatagridIndexPath(string $datagridIndexPath): void
    {
        $this->datagridIndexPath = $datagridIndexPath;
    }

    public function setSearchAutocompletePath(string $searchAutocompletePath): void
    {
        $this->searchAutocompletePath = $searchAutocompletePath;
    }

    public function setDataParameters(array $dataParameters): void
    {
        $this->dataParameters = $dataParameters;
    }
}
