<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Api\Processor\Order;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\OrderBundle\Api\Processor\Order\OrderLineItemProductProcessor;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\ChainProcessor\ContextInterface;

class OrderLineItemProductProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var OrderLineItemProductProcessor
     */
    protected $testedProcessor;

    public function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->testedProcessor = new OrderLineItemProductProcessor($this->doctrineHelper);
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
     * @return EntityRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createEntityRepositoryMock()
    {
        return $this->createMock(EntityRepository::class);
    }

    /**
     * @return EntityRepository|\PHPUnit_Framework_MockObject_MockObject
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
        $entityRepositoryMock = $this->createEntityRepositoryMock();
        $productMock = $this->createProductMock();

        $contextMock
            ->expects(static::any())
            ->method('getResult')
            ->willReturn($orderLineItemMock);

        $contextMock
            ->expects(static::any())
            ->method('getRequestData')
            ->willReturn($requestData);

        $this->doctrineHelper
            ->expects(static::once())
            ->method('getEntityRepository')
            ->with(Product::class)
            ->willReturn($entityRepositoryMock);

        $entityRepositoryMock
            ->expects(static::once())
            ->method('findOneBy')
            ->with(['sku' => $productSku])
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

        $this->doctrineHelper
            ->expects(static::never())
            ->method('getEntityRepository');

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
        $entityRepositoryMock = $this->createEntityRepositoryMock();

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

        $this->doctrineHelper
            ->expects(static::once())
            ->method('getEntityRepository')
            ->with(Product::class)
            ->willReturn($entityRepositoryMock);

        $this->testedProcessor->process($contextMock);
    }
}
