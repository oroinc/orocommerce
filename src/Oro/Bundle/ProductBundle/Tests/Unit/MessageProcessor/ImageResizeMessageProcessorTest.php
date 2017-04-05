<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\MessageProcessor;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Util\JSON;

class ImageResizeMessageProcessorTest extends AbstractImageResizeMessageProcessorTest
{
    public function testProcessInvalidJson()
    {
        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process(
            $this->prepareMessage('not valid json'),
            $this->prepareSession()
        ));
    }

    public function testProcessInvalidData()
    {
        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process(
            $this->prepareMessage(JSON::encode(['abc'])),
            $this->prepareSession()
        ));
    }

    public function testProcessProductImageNotFound()
    {
        $this->imageRepository->find(self::PRODUCT_IMAGE_ID)->willReturn(null);

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process(
            $this->prepareValidMessage(),
            $this->prepareSession()
        ));
    }

    public function testResizeValidData()
    {
        $this->prepareDependencies();

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process(
            $this->prepareValidMessage(),
            $this->prepareSession()
        ));
    }
}
