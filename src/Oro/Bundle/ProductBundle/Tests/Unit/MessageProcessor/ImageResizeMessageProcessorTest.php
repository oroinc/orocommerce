<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\MessageProcessor;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\ImageResizeManagerInterface;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\ProductBundle\MessageProcessor\ImageResizeMessageProcessor;
use Oro\Bundle\ProductBundle\Provider\ProductImagesDimensionsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Exception\InvalidArgumentException as MessageQueueInvalidArgumentException;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class ImageResizeMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    const PRODUCT_IMAGE_ID = 1;
    const FORCE_OPTION = false;

    const ORIGINAL = 'original';
    const LARGE = 'large';
    const SMALL = 'small';

    const PATH_ORIGINAL = 'path_original';
    const PATH_LARGE = 'path_large';
    const PATH_SMALL = 'path_small';

    const CONTENT_ORIGINAL = 'content_original';
    const CONTENT_LARGE = 'content_large';

    /**
     * @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $imageRepository;

    /**
     * @var ProductImagesDimensionsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $imageDimensionsProvider;

    /**
     * @var ImageResizeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $imageResizeManager;

    /**
     * @var array
     */
    protected static $validData = [
        'productImageId' => self::PRODUCT_IMAGE_ID,
        'force' => self::FORCE_OPTION
    ];

    /**
     * @var ImageResizeMessageProcessor
     */
    protected $processor;

    public function setUp()
    {
        $this->imageRepository = $this->createMock(EntityRepository::class);
        $this->imageDimensionsProvider = $this->createMock(ProductImagesDimensionsProvider::class);
        $this->imageResizeManager = $this->createMock(ImageResizeManagerInterface::class);

        $this->processor = new ImageResizeMessageProcessor(
            $this->imageRepository,
            $this->imageDimensionsProvider,
            $this->imageResizeManager
        );
    }

    /**
     * @param string $body
     *
     * @return DbalMessage
     */
    protected function prepareMessage($body)
    {
        $message = new DbalMessage();
        $message->setBody($body);

        return $message;
    }

    /**
     * @return object|SessionInterface
     */
    protected function prepareSession()
    {
        return $this->prophesize(SessionInterface::class)->reveal();
    }

    /**
     * @param array|null $dimensions
     * @return DbalMessage
     */
    protected function prepareValidMessage(array $dimensions = null)
    {
        $data = self::$validData;
        if ($dimensions) {
            $data['dimensions'] = $dimensions;
        }
        return $this->prepareMessage(JSON::encode($data));
    }

    public function testProcessInvalidJson()
    {
        $this->expectException(MessageQueueInvalidArgumentException::class);
        $this->processor->process(
            $this->prepareMessage('not valid json'),
            $this->prepareSession()
        );
    }

    public function testProcessInvalidData()
    {
        $this->expectException(MessageQueueInvalidArgumentException::class);
        $this->processor->process(
            $this->prepareMessage(JSON::encode(['abc'])),
            $this->prepareSession()
        );
    }

    public function testProcessProductImageNotFound()
    {
        $this->imageRepository->expects($this->once())
            ->method('find')
            ->with(self::PRODUCT_IMAGE_ID)
            ->willReturn(null);

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process(
            $this->prepareValidMessage(),
            $this->prepareSession()
        ));
    }

    public function testResizeValidDataWithoutPassedDimensions()
    {
        $image = $this->prepareImageMock();
        $this->imageResizeManager->expects($this->exactly(3))
            ->method('applyFilter')
            ->withConsecutive(
                [$image, self::ORIGINAL, false],
                [$image, self::LARGE, false],
                [$image, self::SMALL, false]
            );

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process(
            $this->prepareValidMessage(),
            $this->prepareSession()
        ));
    }

    public function testResizeValidDataWithPassedNullDimensions()
    {
        $image = $this->prepareImageMock();
        $this->imageResizeManager->expects($this->exactly(3))
            ->method('applyFilter')
            ->withConsecutive(
                [$image, self::ORIGINAL, false],
                [$image, self::LARGE, false],
                [$image, self::SMALL, false]
            );

        $data = self::$validData;
        $data['dimensions'] = null;
        $this->processor->process(
            $this->prepareMessage(JSON::encode($data)),
            $this->prepareSession()
        );
    }

    public function testResizeValidDataWithPassedDimensions()
    {
        $image = $this->prepareImageMock();
        $this->imageResizeManager->expects($this->exactly(2))
            ->method('applyFilter')
            ->withConsecutive(
                [$image, self::ORIGINAL, false],
                [$image, self::SMALL, false]
            );

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process(
            $this->prepareValidMessage([self::ORIGINAL, self::SMALL]),
            $this->prepareSession()
        ));
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function prepareImageMock()
    {
        $image = $this->createMock(File::class);
        $productImage = $this->createMock(StubProductImage::class);
        $productImage->expects($this->any())
            ->method('getImage')
            ->willReturn($image);

        $this->imageDimensionsProvider->expects($this->once())
            ->method('getDimensionsForProductImage')
            ->with($productImage)
            ->willReturn(
                [
                    'main' => new ThemeImageTypeDimension(self::ORIGINAL, null, null),
                    'listing' => new ThemeImageTypeDimension(self::LARGE, 100, 100),
                    'additional' => new ThemeImageTypeDimension(self::SMALL, 50, 50)
                ]
            );

        $this->imageRepository->expects($this->once())
            ->method('find')
            ->with(self::PRODUCT_IMAGE_ID)
            ->willReturn($productImage);

        return $image;
    }
}
