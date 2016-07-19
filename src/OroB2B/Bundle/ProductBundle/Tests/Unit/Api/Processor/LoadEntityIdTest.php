<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorOrmRelatedTestCase;

use OroB2B\Bundle\ProductBundle\Api\Processor\LoadEntityId;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class LoadEntityIdTest extends GetProcessorOrmRelatedTestCase
{
    /** @var SingleItemContext||\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var LoadEntityId */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->context = $this->getMockBuilder(SingleItemContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new LoadEntityId();
    }

    public function testProcessWhenIdExists()
    {
        $this->context->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->context->expects($this->never())
            ->method('getResult');

        $this->processor->process($this->context);
    }
    
    public function testProcessWhenIdNullAndEntityInexistent()
    {
        $this->context->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->context->expects($this->once())
            ->method('getResult')
            ->willReturn(null);

        $this->context->expects($this->never())
            ->method('setId');

        $this->processor->process($this->context);
    }

    public function testProcessSetsId()
    {
        $this->context->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())
            ->method(LoadEntityId::METHOD)
            ->willReturn(1);

        $this->context->expects($this->once())
            ->method('getResult')
            ->willReturn($product);

        $this->context->expects($this->once())
            ->method('setId')
            ->with(1);

        $this->processor->process($this->context);
    }
}
