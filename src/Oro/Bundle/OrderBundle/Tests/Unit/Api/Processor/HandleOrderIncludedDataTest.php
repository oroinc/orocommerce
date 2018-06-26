<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\OrderBundle\Api\Processor\HandleOrderIncludedData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\SkuCachedProductProvider;

class HandleOrderIncludedDataTest extends FormProcessorTestCase
{
    /**
     * @var SkuCachedProductProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $skuCachedProductProviderMock;

    /**
     * @var HandleOrderIncludedData
     */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->skuCachedProductProviderMock = $this->createMock(SkuCachedProductProvider::class);

        $this->processor = new HandleOrderIncludedData($this->skuCachedProductProviderMock);
    }

    public function testSuccessfulProcess()
    {
        $productSku = '4HC51';
        $includedData = [
            [
                'data' =>
                    [
                        'type' => 'orderlineitems',
                        'id' => '123',
                        'attributes' => ['productSku' => $productSku],
                    ],
            ],
        ];

        $this->skuCachedProductProviderMock
            ->expects(static::once())
            ->method('addSkuToCache')
            ->with($productSku);

        $this->context->setResult($this->createMock(Order::class));
        $this->context->setIncludedData($includedData);
        $this->processor->process($this->context);
    }

    public function testWrongResult()
    {
        $this->skuCachedProductProviderMock
            ->expects(static::never())
            ->method('addSkuToCache');

        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }

    public function testNoIncludedData()
    {
        $this->skuCachedProductProviderMock
            ->expects(static::never())
            ->method('addSkuToCache');

        $this->context->setResult($this->createMock(Order::class));
        $this->context->setIncludedData([]);
        $this->processor->process($this->context);
    }

    /**
     * @param array $includedData
     * @dataProvider wrongIncludedDataTestProvider
     */
    public function testWrongIncludedData(array $includedData)
    {
        $this->skuCachedProductProviderMock
            ->expects(static::never())
            ->method('addSkuToCache');

        $this->context->setResult($this->createMock(Order::class));
        $this->context->setIncludedData($includedData);
        $this->processor->process($this->context);
    }

    /**
     * @return array
     */
    public function wrongIncludedDataTestProvider()
    {
        return [
            'Empty included data' => [[]],
            'only data field' => [
                [
                    [
                        'data' => []
                    ],
                ]
            ],
            'data and wrong type' => [
                [
                    [
                        'data' =>
                            [
                                'type' => 'wrong type',
                            ],
                    ],
                ]
            ],
            'data and correct type' => [
                [
                    [
                        'data' =>
                            [
                                'type' => 'orderlineitems',
                            ],
                    ],
                ]
            ],
            'data, type, attributes' => [
                [
                    [
                        'data' =>
                            [
                                'type' => 'orderlineitems',
                                'attributes' => []
                            ],
                    ],
                ]
            ],
            'data, type, attributes, productsSku, freeForm' => [
                [
                    [
                        'data' =>
                            [
                                'type' => 'orderlineitems',
                                'attributes' => ['productSku' => '123', 'freeFormProduct' => 'someFreeForm'],
                            ],
                    ],
                ]
            ],
            'data, type, attributes, productsSku, relationships' => [
                [
                    [
                        'data' =>
                            [
                                'type' => 'orderlineitems',
                                'attributes' => ['productSku' => '123'],
                                'relationships' => ['product' => []]
                            ],
                    ],
                ]
            ],
        ];
    }
}
