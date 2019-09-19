<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\MessageProcessor;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Exception\InvalidArgumentException;
use Oro\Component\MessageQueue\Util\JSON;

class ImageResizeMessageProcessorTest extends AbstractImageResizeMessageProcessorTest
{
    public function testProcessInvalidJson()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->processor->process(
            $this->prepareMessage('not valid json'),
            $this->prepareSession()
        );
    }

    public function testProcessInvalidData()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->processor->process(
            $this->prepareMessage(JSON::encode(['abc'])),
            $this->prepareSession()
        );
    }

    public function testProcessProductImageNotFound()
    {
        $this->imageRepository->find(self::PRODUCT_IMAGE_ID)->willReturn(null);

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process(
            $this->prepareValidMessage(),
            $this->prepareSession()
        ));
    }

    public function testResizeValidDataWithoutPassedDimensions()
    {
        $image = $this->getImage();
        $this->prepareDependencies($image);

        $this->assertImageResizesCalledForAllDimensions($image);

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process(
            $this->prepareValidMessage(),
            $this->prepareSession()
        ));
    }

    public function testResizeValidDataWithPassedNullDimensions()
    {
        $image = $this->getImage();
        $this->prepareDependencies($image);

        $this->assertImageResizesCalledForAllDimensions($image);

        $data = self::$validData;
        $data['dimensions'] = null;
        $this->processor->process(
            $this->prepareMessage(JSON::encode($data)),
            $this->prepareSession()
        );
    }

    public function testResizeValidDataWithPassedDimensions()
    {
        $image = $this->getImage();
        $this->prepareDependencies($image);

        $this->attachmentManager->getFilteredImageUrl($image, self::ORIGINAL)
            ->shouldBeCalled()
            ->willReturn(self::PATH_ORIGINAL);
        $this->attachmentManager->getFilteredImageUrl($image, self::LARGE)->shouldNotBeCalled();
        $this->attachmentManager->getFilteredImageUrl($image, self::SMALL)
            ->shouldBeCalled()
            ->willReturn(self::PATH_SMALL);

        $this->mediaCacheManager->exists(self::PATH_ORIGINAL)->shouldBeCalled()->willReturn(false);
        $this->mediaCacheManager->exists(self::PATH_LARGE)->shouldNotBeCalled();
        $this->mediaCacheManager->exists(self::PATH_SMALL)->shouldBeCalled()->willReturn(true);

        $filteredImageOriginal = $this->prophesize(BinaryInterface::class);
        $filteredImageOriginal->getContent()->willReturn(self::CONTENT_ORIGINAL);

        $this->imageResizer->resizeImage($image, self::ORIGINAL)
            ->shouldBeCalled()
            ->willReturn($filteredImageOriginal->reveal());
        $this->imageResizer->resizeImage($image, self::LARGE)->shouldNotBeCalled();
        $this->imageResizer->resizeImage($image, self::SMALL)->shouldNotBeCalled();

        $this->mediaCacheManager->store(self::CONTENT_ORIGINAL, self::PATH_ORIGINAL)->shouldBeCalled();
        $this->mediaCacheManager->store(self::CONTENT_LARGE, self::PATH_LARGE)->shouldNotBeCalled();
        $this->mediaCacheManager->store(self::CONTENT_LARGE, self::PATH_SMALL)->shouldNotBeCalled();

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process(
            $this->prepareValidMessage([self::ORIGINAL, self::SMALL]),
            $this->prepareSession()
        ));
    }

    /**
     * @param \Prophecy\Prophecy\ObjectProphecy $image
     */
    private function assertImageResizesCalledForAllDimensions(\Prophecy\Prophecy\ObjectProphecy $image): void
    {
        $this->attachmentManager->getFilteredImageUrl($image, self::ORIGINAL)
            ->shouldBeCalled()
            ->willReturn(self::PATH_ORIGINAL);
        $this->attachmentManager->getFilteredImageUrl($image, self::LARGE)
            ->shouldBeCalled()
            ->willReturn(self::PATH_LARGE);
        $this->attachmentManager->getFilteredImageUrl($image, self::SMALL)
            ->shouldBeCalled()
            ->willReturn(self::PATH_SMALL);

        $this->mediaCacheManager->exists(self::PATH_ORIGINAL)->shouldBeCalled()->willReturn(false);
        $this->mediaCacheManager->exists(self::PATH_LARGE)->shouldBeCalled()->willReturn(false);
        $this->mediaCacheManager->exists(self::PATH_SMALL)->shouldBeCalled()->willReturn(true);

        $filteredImageOriginal = $this->prophesize(BinaryInterface::class);
        $filteredImageOriginal->getContent()->willReturn(self::CONTENT_ORIGINAL);
        $filteredImageLarge = $this->prophesize(BinaryInterface::class);
        $filteredImageLarge->getContent()->willReturn(self::CONTENT_LARGE);

        $this->imageResizer->resizeImage($image, self::ORIGINAL)
            ->shouldBeCalled()
            ->willReturn($filteredImageOriginal->reveal());
        $this->imageResizer->resizeImage($image, self::LARGE)
            ->shouldBeCalled()
            ->willReturn($filteredImageLarge->reveal());
        $this->imageResizer->resizeImage($image, self::SMALL)->shouldNotBeCalled();

        $this->mediaCacheManager->store(self::CONTENT_ORIGINAL, self::PATH_ORIGINAL)->shouldBeCalled();
        $this->mediaCacheManager->store(self::CONTENT_LARGE, self::PATH_LARGE)->shouldBeCalled();
    }
}
