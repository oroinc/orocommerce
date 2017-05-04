<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Api\Processor\Order;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\OrderBundle\Api\Processor\Order\OrderProductCacherProcessor;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\SkuCachedProductProvider;
use Oro\Component\ChainProcessor\ContextInterface;

class OrderProductCacherProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SkuCachedProductProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $skuCachedProductProviderMock;

    /**
     * @var OrderProductCacherProcessor
     */
    protected $testedProcessor;

    public function setUp()
    {
        $this->skuCachedProductProviderMock = $this->createMock(SkuCachedProductProvider::class);

        $this->testedProcessor = new OrderProductCacherProcessor($this->skuCachedProductProviderMock);
    }

    /**
     * @return FormContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createContextMock()
    {
        return $this->createMock(FormContext::class);
    }

    public function testSuccessfulProcess()
    {
        $productSku = '4HC51';
        $contextMock = $this->createContextMock();
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

        $contextMock
            ->expects(static::once())
            ->method('getResult')
            ->willReturn($this->createMock(Order::class));

        $contextMock
            ->expects(static::once())
            ->method('getIncludedData')
            ->willReturn($includedData);

        $this->skuCachedProductProviderMock
            ->expects(static::once())
            ->method('addSkuToCache')
            ->with($productSku);

        $this->testedProcessor->process($contextMock);
    }

    public function testWrongContext()
    {
        $contextMock = $this->createMock(ContextInterface::class);

        $contextMock
            ->expects(static::never())
            ->method('getResult');

        $this->skuCachedProductProviderMock
            ->expects(static::never())
            ->method('addSkuToCache');

        $this->testedProcessor->process($contextMock);
    }

    public function testWrongResult()
    {
        $contextMock = $this->createContextMock();

        $contextMock
            ->expects(static::once())
            ->method('getResult')
            ->willReturn(new \stdClass());

        $this->skuCachedProductProviderMock
            ->expects(static::never())
            ->method('addSkuToCache');

        $this->testedProcessor->process($contextMock);
    }

    public function testNoIncludedData()
    {
        $contextMock = $this->createContextMock();

        $contextMock
            ->expects(static::once())
            ->method('getResult')
            ->willReturn($this->createMock(Order::class));

        $contextMock
            ->expects(static::once())
            ->method('getIncludedData')
            ->willReturn([]);

        $this->skuCachedProductProviderMock
            ->expects(static::never())
            ->method('addSkuToCache');

        $this->testedProcessor->process($contextMock);
    }

    /**
     * @param array $includedData
     * @dataProvider wrongIncludedDataTestProvider
     */
    public function testWrongIncludedData(array $includedData)
    {
        $contextMock = $this->createContextMock();

        $contextMock
            ->expects(static::once())
            ->method('getResult')
            ->willReturn($this->createMock(Order::class));

        $contextMock
            ->expects(static::once())
            ->method('getIncludedData')
            ->willReturn($includedData);

        $this->skuCachedProductProviderMock
            ->expects(static::never())
            ->method('addSkuToCache');

        $this->testedProcessor->process($contextMock);
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
