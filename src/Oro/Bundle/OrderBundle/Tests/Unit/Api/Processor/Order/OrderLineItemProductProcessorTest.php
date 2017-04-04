<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Api\Processor\Order;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\OrderBundle\Api\Processor\Order\OrderLineItemProductProcessor;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Provider\SkuCachedProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\ChainProcessor\ContextInterface;

class OrderLineItemProductProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SkuCachedProductProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $skuCachedProductProviderMock;

    /**
     * @var OrderLineItemProductProcessor
     */
    protected $testedProcessor;

    public function setUp()
    {
        $this->skuCachedProductProviderMock = $this->createMock(SkuCachedProductProvider::class);

        $this->testedProcessor = new OrderLineItemProductProcessor($this->skuCachedProductProviderMock);
    }

    /**
     * @return FormContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createContextMock()
    {
        return $this->createMock(FormContext::class);
    }

    /**
     * @return OrderLineItem|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createOrderLineItemMock()
    {
        return $this->createMock(OrderLineItem::class);
    }

    /**
     * @return Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createProductMock()
    {
        return $this->createMock(Product::class);
    }

    public function testSuccessfulProcess()
    {
        $productSku = 'someProductSku';
        $requestData = ['productSku' => $productSku];
        $productId = 1;
        $contextMock = $this->createContextMock();
        $orderLineItemMock = $this->createOrderLineItemMock();
        $productMock = $this->createProductMock();

        $contextMock
            ->expects(static::any())
            ->method('getResult')
            ->willReturn($orderLineItemMock);

        $contextMock
            ->expects(static::any())
            ->method('getRequestData')
            ->willReturn($requestData);

        $this->skuCachedProductProviderMock
            ->expects(static::once())
            ->method('getBySku')
            ->with($productSku)
            ->willReturn($productMock);

        $productMock
            ->expects(static::once())
            ->method('getId')
            ->willReturn($productId);

        $expectedRequestData = $requestData;
        $expectedRequestData['product'] = [
            'class' => Product::class,
            'id' => $productId,
        ];

        $contextMock
            ->expects(static::once())
            ->method('setRequestData')
            ->with($expectedRequestData);

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
            ->method('getBySku');

        $this->testedProcessor->process($contextMock);
    }

    public function testNoRequestData()
    {
        $contextMock = $this->createContextMock();

        $contextMock
            ->expects(static::once())
            ->method('getRequestData')
            ->willReturn(null);

        $contextMock
            ->expects(static::never())
            ->method('setRequestData');

        $this->testedProcessor->process($contextMock);
    }

    public function testWrongResult()
    {
        $contextMock = $this->createContextMock();

        $contextMock
            ->expects(static::once())
            ->method('getResult')
            ->willReturn(new \stdClass());

        $contextMock
            ->expects(static::never())
            ->method('setRequestData');

        $this->testedProcessor->process($contextMock);
    }

    /**
     * @param array $requestData
     *
     * @dataProvider wrongRequestTestDataProvider
     */
    public function testWrongRequestData(array $requestData)
    {
        $contextMock = $this->createContextMock();

        $contextMock
            ->expects(static::any())
            ->method('getResult')
            ->willReturn($this->createOrderLineItemMock());

        $contextMock
            ->expects(static::never())
            ->method('setRequestData');

        $contextMock
            ->expects(static::once())
            ->method('getRequestData')
            ->willReturn($requestData);

        $this->testedProcessor->process($contextMock);
    }

    /**
     * @return array
     */
    public function wrongRequestTestDataProvider()
    {
        return [
            [['freeFormProduct' => 'freeFormProduct']],
            [['product' => 'product']],
            [[]]
        ];
    }

    public function testNoProduct()
    {
        $contextMock = $this->createContextMock();

        $contextMock
            ->expects(static::any())
            ->method('getResult')
            ->willReturn($this->createOrderLineItemMock());

        $contextMock
            ->expects(static::never())
            ->method('setRequestData');

        $contextMock
            ->expects(static::once())
            ->method('getRequestData')
            ->willReturn(['productSku' => 'productSku']);

        $this->testedProcessor->process($contextMock);
    }
}
