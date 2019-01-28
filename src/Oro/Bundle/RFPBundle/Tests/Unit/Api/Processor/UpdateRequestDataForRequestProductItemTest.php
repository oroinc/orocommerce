<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\RFPBundle\Api\Processor\UpdateRequestDataForRequestProductItem;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;

class UpdateRequestDataForRequestProductItemTest extends \PHPUnit\Framework\TestCase
{
    /** @var UpdateRequestDataForRequestProductItem */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->processor = new UpdateRequestDataForRequestProductItem();
    }

    public function testProcessWithoutRequestData()
    {
        /** @var FormContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(FormContext::class);
        $context->expects($this->any())->method('getRequestData')->willReturn([]);
        $context->expects($this->never())->method('setRequestData');

        $this->processor->process($context);
    }

    public function testProcessWithoutRequestProductItem()
    {
        /** @var FormContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(FormContext::class);
        $context->expects($this->any())->method('getRequestData')->willReturn(['currency' => 'USD', 'value' => 10]);
        $context->expects($this->any())->method('getResult')->willReturn(null);
        $context->expects($this->never())->method('setRequestData');

        $this->processor->process($context);
    }

    /**
     * @dataProvider requestDataProvider
     *
     * @param array $requestData
     * @param RequestProductItem $expectedItem
     */
    public function testProcess(array $requestData, RequestProductItem $expectedItem)
    {
        $actualItem = new RequestProductItem();

        /** @var FormContext|\PHPUnit\Framework\MockObject\MockObject $context */
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
        $productItem = new RequestProductItem();
        $productItem->setPrice(Price::create(10, 'USD'));

        yield 'empty request' => [
            'requestData' => [],
            'expectedItem' => new RequestProductItem(),
        ];

        yield 'empty currency' => [
            'requestData' => ['value' => 10],
            'expectedItem' => new RequestProductItem(),
        ];

        yield 'empty value' => [
            'requestData' => ['currency' => 'USD'],
            'expectedItem' => new RequestProductItem(),
        ];

        yield 'value & currency exist' => [
            'requestData' => ['currency' => 'USD', 'value' => 10],
            'expectedItem' => $productItem,
        ];
    }
}
