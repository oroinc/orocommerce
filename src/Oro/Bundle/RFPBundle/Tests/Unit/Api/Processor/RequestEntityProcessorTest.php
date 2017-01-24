<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\RFPBundle\Api\Processor\RequestEntityProcessor;

class RequestEntityProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var RequestEntityProcessor */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->processor = new RequestEntityProcessor();
    }

    public function testProcess()
    {
        $requestData = [
            'customer_status' => ['open'],
            'internal_status' => ['open'],
            'createdAt' => 10,
            'updatedAt' => 10,
            'requestAdditionalNotes' => [],
            'firstName' => 'testName'
        ];
        $expectedData = [
            'firstName' => 'testName'
        ];

        $context = $this->createMock(FormContext::class);
        $context->expects($this->any())->method('getRequestData')->willReturn($requestData);

        $context->expects($this->once())->method('setRequestData')
            ->with($this->identicalTo($expectedData));

        $this->processor->process($context);
    }
}
