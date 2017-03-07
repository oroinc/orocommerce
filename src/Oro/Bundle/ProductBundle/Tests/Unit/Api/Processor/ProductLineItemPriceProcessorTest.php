<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ContextInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceSetterAwareInterface;
use Oro\Bundle\ProductBundle\Api\Processor\ProductLineItemPriceProcessor;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

class ProductLineItemPriceProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductLineItemPriceProcessor */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->processor = new ProductLineItemPriceProcessor();
    }

    public function testProcessWithNotFormContext()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->never())->method($this->anything());

        $this->processor->process($context);
    }

    public function testProcessWithoutRequestData()
    {
        /** @var FormContext|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(FormContext::class);
        $context->expects($this->any())->method('getRequestData')->willReturn([]);
        $context->expects($this->never())->method('setRequestData');

        $this->processor->process($context);
    }

    public function testProcessWithoutRequestProductItem()
    {
        /** @var FormContext|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(FormContext::class);
        $context->expects($this->any())->method('getRequestData')->willReturn(['currency' => 'USD', 'value' => 10]);
        $context->expects($this->any())->method('getResult')->willReturn(null);
        $context->expects($this->never())->method('setRequestData');

        $this->processor->process($context);
    }

    /**
     * @dataProvider requestDataProvider
     *
     * @param array                    $requestData
     * @param ProductLineItemInterface $expectedItem
     */
    public function testProcess(array $requestData, ProductLineItemInterface $expectedItem)
    {
        $actualItem = $this->createProductLineItemMock();

        /** @var FormContext|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(FormContext::class);
        $context->expects($this->any())->method('getRequestData')->willReturn($requestData);
        $context->expects($this->any())->method('getResult')->willReturn($actualItem);

        $this->processor->process($context);
        $this->assertEquals($expectedItem, $actualItem);
    }

    /**
     * @return \Generator
     */
    public function requestDataProvider()
    {
        $productItem = $this->createProductLineItemMock(Price::create(10, 'USD'));

        yield 'empty request' => [
            'requestData' => [],
            'expectedItem' => $this->createProductLineItemMock(),
        ];

        yield 'empty currency' => [
            'requestData' => ['value' => 10],
            'expectedItem' => $this->createProductLineItemMock(),
        ];

        yield 'empty value' => [
            'requestData' => ['currency' => 'USD'],
            'expectedItem' => $this->createProductLineItemMock(),
        ];

        yield 'value & currency exist' => [
            'requestData' => ['currency' => 'USD', 'value' => 10],
            'expectedItem' => $productItem,
        ];
    }

    /**
     * @param Price|null $price
     *
     * @return ProductLineItemInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createProductLineItemMock(Price $price = null)
    {
        $mock = $this->createMock([ProductLineItemInterface::class, PriceSetterAwareInterface::class]);

        if (null === $price) {
            $mock
                ->expects($this->any())
                ->method('getPrice')
                ->willReturn($price);
        }

        return $mock;
    }
}
