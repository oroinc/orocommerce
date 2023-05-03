<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ProductSearchAggregationTest extends FrontendRestJsonApiTestCase
{
    use WebsiteSearchExtensionTrait;
    use ProductSearchEngineCheckTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product.yml',
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product_prices.yml'
        ]);
    }

    protected function postFixtureLoad()
    {
        parent::postFixtureLoad();
        $this->reindexProductData();
    }

    /**
     * @return bool
     */
    private function isElasticSearchEngine()
    {
        return
            class_exists('Oro\Bundle\ElasticSearchBundle\Engine\ElasticSearch')
            && \Oro\Bundle\ElasticSearchBundle\Engine\ElasticSearch::ENGINE_NAME === $this->getSearchEngine();
    }

    public function testSeveralAggregates()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'productType count,testAttrInteger max'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'productTypeCount'   => [
                            ['value' => 'configurable', 'count' => 3],
                            ['value' => 'simple', 'count' => 2]
                        ],
                        'testAttrIntegerMax' => 141
                    ]
                ]
            ],
            $response
        );
    }

    public function testAliases()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'productType count productTypes,testAttrInteger max maxInt'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'productTypes' => [
                            ['value' => 'configurable', 'count' => 3],
                            ['value' => 'simple', 'count' => 2]
                        ],
                        'maxInt' => 141
                    ]
                ]
            ],
            $response
        );
    }

    public function testCountByBooleanWithBothFalseAndTrueValues()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'newArrival count'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'newArrivalCount' => [
                            ['value' => 0, 'count' => 5],
                            ['value' => 1, 'count' => 1]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCountByBooleanWithFalseValueOnly()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'newArrival count', 'searchQuery' => 'newArrival = 0'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'newArrivalCount' => [
                            ['value' => 0, 'count' => 5]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCountByBooleanWithTrueValueOnly()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'newArrival count', 'searchQuery' => 'newArrival = 1'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'newArrivalCount' => [
                            ['value' => 1, 'count' => 1]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCountByString()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'productType count'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'productTypeCount' => [
                            ['value' => 'configurable', 'count' => 3],
                            ['value' => 'simple', 'count' => 2]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCountByInteger()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrInteger count'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrIntegerCount' => [
                            ['value' => 1, 'count' => 1],
                            ['value' => 12, 'count' => 1],
                            ['value' => 120, 'count' => 1],
                            ['value' => 123, 'count' => 1]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCountByIntegerWhenNoProductsWithThisValue()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrInteger count', 'searchQuery' => 'sku = CPSKU3'],
                'fields' => ['productsearch' => 'sku']
            ]
        );

        $content = self::jsonToArray($response->getContent());
        self::assertNotContains('meta', $content);
    }

    public function testCountByEnumForElasticSearchEngine()
    {
        if (!$this->isElasticSearchEngine()) {
            $this->markTestSkipped('ElasticSearch search engine is not configured.');
        }
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrEnum count'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrEnumCount' => [
                            ['value' => 'option1', 'count' => 3],
                            ['value' => 'option2', 'count' => 3],
                            ['value' => 'option3', 'count' => 1],
                            ['value' => 'option4', 'count' => 1],
                            ['value' => 'option5', 'count' => 1],
                            ['value' => 'option6', 'count' => 1],
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCountByMultiEnumForElasticSearchEngine()
    {
        if (!$this->isElasticSearchEngine()) {
            $this->markTestSkipped('ElasticSearch search engine is not configured.');
        }
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrMultiEnum count'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrMultiEnumCount' => [
                            ['value' => 'option1', 'count' => 1],
                            ['value' => 'option2', 'count' => 1]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCountByEnumForOrmEngine()
    {
        if (!$this->isOrmEngine()) {
            $this->markTestSkipped('This test works only with ORM search engine.');
        }
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrEnum count'],
                'fields' => ['productsearch' => 'sku']
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The field "testAttrEnum" is not supported.',
                'source' => ['parameter' => 'filter[aggregations]']
            ],
            $response
        );
    }

    public function testCountByMultiEnumForOrmEngine()
    {
        if (!$this->isOrmEngine()) {
            $this->markTestSkipped('This test works only with ORM search engine.');
        }
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrMultiEnum count'],
                'fields' => ['productsearch' => 'sku']
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The field "testAttrMultiEnum" is not supported.',
                'source' => ['parameter' => 'filter[aggregations]']
            ],
            $response
        );
    }

    public function testSumByInteger()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrInteger sum'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrIntegerSum' => 397
                    ]
                ]
            ],
            $response
        );
    }

    public function testSumByIntegerWithoutNullValues()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrInteger sum', 'searchQuery' => 'testAttrInteger >= 120'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrIntegerSum' => 384
                    ]
                ]
            ],
            $response
        );
    }

    public function testSumByFloat()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrFloat sum'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrFloatSum' => 6.63
                    ]
                ]
            ],
            $response
        );
    }

    public function testSumByFloatWithoutNullValues()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrFloat sum', 'searchQuery' => 'testAttrFloat >= 1.2'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrFloatSum' => 5.53
                    ]
                ]
            ],
            $response
        );
    }

    public function testMinByInteger()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrInteger min'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrIntegerMin' => 1
                    ]
                ]
            ],
            $response
        );
    }

    public function testMinByIntegerWithLimitByMinValue()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrInteger min', 'searchQuery' => 'testAttrInteger >= 120'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrIntegerMin' => 120
                    ]
                ]
            ],
            $response
        );
    }

    public function testMinByIntegerWhenNoProductsWithThisValue()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrInteger min', 'searchQuery' => 'sku = CPSKU3'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrIntegerMin' => null
                    ]
                ]
            ],
            $response
        );
    }

    public function testMinByFloat()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrFloat min'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrFloatMin' => 1.1
                    ]
                ]
            ],
            $response
        );
    }

    public function testMinByFloatWithLimitByMinValue()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrFloat min', 'searchQuery' => 'testAttrFloat >= 1.2'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrFloatMin' => 1.2
                    ]
                ]
            ],
            $response
        );
    }

    public function testMinByFloatWhenNoProductsWithThisValue()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrFloat min', 'searchQuery' => 'sku = CPSKU1'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrFloatMin' => null
                    ]
                ]
            ],
            $response
        );
    }

    public function testMaxByInteger()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrInteger max'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrIntegerMax' => 141
                    ]
                ]
            ],
            $response
        );
    }

    public function testMaxByIntegerWithLimitByMaxValue()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrInteger max', 'searchQuery' => 'testAttrInteger <= 120'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrIntegerMax' => 120
                    ]
                ]
            ],
            $response
        );
    }

    public function testMaxByIntegerWhenNoProductsWithThisValue()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrInteger max', 'searchQuery' => 'sku = CPSKU3'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrIntegerMax' => null
                    ]
                ]
            ],
            $response
        );
    }

    public function testMaxByFloat()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrFloat max'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrFloatMax' => 1.6
                    ]
                ]
            ],
            $response
        );
    }

    public function testMaxByFloatWithLimitByMaxValue()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrFloat max', 'searchQuery' => 'testAttrFloat <= 1.2'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrFloatMax' => 1.2
                    ]
                ]
            ],
            $response
        );
    }

    public function testMaxByFloatWhenNoProductsWithThisValue()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrFloat max', 'searchQuery' => 'sku = CPSKU1'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrFloatMax' => null
                    ]
                ]
            ],
            $response
        );
    }

    public function testAvgByInteger()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrInteger avg'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrIntegerAvg' => 79.4
                    ]
                ]
            ],
            $response
        );
    }

    public function testAvgByIntegerWithLimitByMinValue()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrInteger avg', 'searchQuery' => 'testAttrInteger >= 120'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrIntegerAvg' => 128
                    ]
                ]
            ],
            $response
        );
    }

    public function testAvgByFloat()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrFloat avg'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrFloatAvg' => 1.326
                    ]
                ]
            ],
            $response
        );
    }

    public function testAvgByFloatWithLimitByMinValue()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'testAttrFloat avg', 'searchQuery' => 'testAttrFloat >= 1.2'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'testAttrFloatAvg' => 1.3825
                    ]
                ]
            ],
            $response
        );
    }

    public function testSumByMinimalPrice()
    {
        if (!$this->isOrmEngine()) {
            $this->markTestSkipped('This test works only with ORM search engine.');
        }

        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'minimalPrice sum'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'minimalPriceSum' => 21
                    ]
                ]
            ],
            $response
        );
    }

    public function testSumByMinimalPriceWithUnit()
    {
        if (!$this->isOrmEngine()) {
            $this->markTestSkipped('This test works only with ORM search engine.');
        }

        $response = $this->cget(
            ['entity' => 'productsearch'],
            [
                'filter' => ['aggregations' => 'minimalPrice_item sum minimalPriceSum'],
                'fields' => ['productsearch' => 'sku']
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'aggregatedData' => [
                        'minimalPriceSum' => 21
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToAggregateByNotSupportedField()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['aggregations' => 'name123 count']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The field "name123" is not supported.',
                'source' => ['parameter' => 'filter[aggregations]']
            ],
            $response
        );
    }

    public function testTryToAggregateByNotSupportedFunction()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['aggregations' => 'testAttrInteger other']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The aggregating function "other" is not supported.',
                'source' => ['parameter' => 'filter[aggregations]']
            ],
            $response
        );
    }

    public function testTryToAggregateByInvalidExpression()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['aggregations' => 'testAttrInteger count,invalid_expr']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The value "invalid_expr" must match one of the following patterns:'
                    . ' "fieldName functionName" or "fieldName functionName resultName".',
                'source' => ['parameter' => 'filter[aggregations]']
            ],
            $response
        );
    }

    public function testTryToAggregateByFunctionThatIsNotSupportedForDataType()
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['aggregations' => 'productType sum']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The aggregating function "sum" is not supported for the field type "text".',
                'source' => ['parameter' => 'filter[aggregations]']
            ],
            $response
        );
    }
}
