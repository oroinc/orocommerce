<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\RFPBundle\Api\Processor\UpdateRequestDataForRequestEntity;

class UpdateRequestDataForRequestEntityTest extends \PHPUnit\Framework\TestCase
{
    /** @var UpdateRequestDataForRequestEntity */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->processor = new UpdateRequestDataForRequestEntity();
    }

    public function testProcessWithoutRequestData()
    {
        /** @var FormContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(FormContext::class);
        $context->expects($this->any())->method('getRequestData')->willReturn([]);
        $context->expects($this->never())->method('setRequestData');

        $this->processor->process($context);
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

        /** @var FormContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(FormContext::class);
        $context->expects($this->any())->method('getRequestData')->willReturn($requestData);
        $context->expects($this->once())->method('setRequestData')->with($this->identicalTo($expectedData));

        $this->processor->process($context);
    }
}
