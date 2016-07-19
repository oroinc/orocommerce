<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorOrmRelatedTestCase;

use OroB2B\Bundle\ProductBundle\Api\Processor\NormalizeProductId;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class NormalizeProductIdTest extends GetProcessorOrmRelatedTestCase
{
    /** @var UpdateContext||\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var NormalizeProductId */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->context = $this->getMockBuilder(UpdateContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new NormalizeProductId();
    }

    public function testProcess()
    {
        $this->context->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->context->expects($this->once())
            ->method('getRequestData')
            ->willReturn([]);

        $this->context->expects($this->once())
            ->method('setRequestData')
            ->with([NormalizeProductId::PRODUCT_IDENTIFIER => 1]);

        $this->context->expects($this->once())
            ->method('setId')
            ->with(null);

        $this->processor->process($this->context);
    }
}
