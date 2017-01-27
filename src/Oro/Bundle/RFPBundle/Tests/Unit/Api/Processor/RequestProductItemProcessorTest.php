<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\RFPBundle\Api\Processor\RequestProductItemProcessor;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;

class RequestProductItemProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var RequestProductItemProcessor */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->processor = new RequestProductItemProcessor();
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

        yield 'empty request' => [
            'requestData' => [],
            'expectedItem' => clone $productItem,
        ];

        yield 'empty currency' => [
            'requestData' => ['value' => 10],
            'expectedItem' => clone $productItem,
        ];

        yield 'empty value' => [
            'requestData' => ['currency' => 'USD'],
            'expectedItem' => clone $productItem,
        ];

        yield 'value & currency exist' => [
            'requestData' => ['currency' => 'USD', 'value' => 10],
            'expectedItem' => (clone $productItem)->setPrice(Price::create(10, 'USD')),
        ];
    }
}
