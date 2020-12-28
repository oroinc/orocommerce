<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\ImageResizeManagerInterface;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\ProductBundle\Async\ResizeProductImageMessageProcessor;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Provider\ProductImagesDimensionsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Exception\InvalidArgumentException as MessageQueueInvalidArgumentException;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class ResizeProductImageMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    private const PRODUCT_IMAGE_ID = 1;
    private const FORCE_OPTION     = false;

    private const ORIGINAL = 'original';
    private const LARGE    = 'large';
    private const SMALL    = 'small';

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var ProductImagesDimensionsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $imageDimensionsProvider;

    /** @var ImageResizeManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $imageResizeManager;

    /** @var ResizeProductImageMessageProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->imageDimensionsProvider = $this->createMock(ProductImagesDimensionsProvider::class);
        $this->imageResizeManager = $this->createMock(ImageResizeManagerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(ProductImage::class)
            ->willReturn($this->em);

        $this->processor = new ResizeProductImageMessageProcessor(
            $doctrine,
            $this->imageDimensionsProvider,
            $this->imageResizeManager
        );
    }

    /**
     * @return SessionInterface
     */
    private function getSession()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return File|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getImageMock(): File
    {
        $image = $this->createMock(File::class);
        $productImage = new StubProductImage();
        $productImage->setImage($image);

        $this->imageDimensionsProvider->expects(self::once())
            ->method('getDimensionsForProductImage')
            ->with($productImage)
            ->willReturn([
                'main'       => new ThemeImageTypeDimension(self::ORIGINAL, null, null),
                'listing'    => new ThemeImageTypeDimension(self::LARGE, 100, 100),
                'additional' => new ThemeImageTypeDimension(self::SMALL, 50, 50)
            ]);

        $this->em->expects(self::once())
            ->method('find')
            ->with(ProductImage::class, self::PRODUCT_IMAGE_ID)
            ->willReturn($productImage);

        return $image;
    }

    public function testProcessInvalidJson()
    {
        $this->expectException(MessageQueueInvalidArgumentException::class);

        $message = new Message();
        $message->setBody('not valid json');

        $this->processor->process($message, $this->getSession());
    }

    public function testProcessInvalidData()
    {
        $this->expectException(MessageQueueInvalidArgumentException::class);

        $message = new Message();
        $message->setBody(JSON::encode(['abc']));

        $this->processor->process($message, $this->getSession());
    }

    public function testProcessProductImageNotFound()
    {
        $message = new Message();
        $message->setBody(JSON::encode([
            'productImageId' => self::PRODUCT_IMAGE_ID,
            'force'          => self::FORCE_OPTION
        ]));

        $this->em->expects(self::once())
            ->method('find')
            ->with(ProductImage::class, self::PRODUCT_IMAGE_ID)
            ->willReturn(null);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessProductImageFileNotFound()
    {
        $message = new Message();
        $message->setBody(JSON::encode([
            'productImageId' => self::PRODUCT_IMAGE_ID,
            'force'          => self::FORCE_OPTION
        ]));

        $this->em->expects(self::once())
            ->method('find')
            ->with(ProductImage::class, self::PRODUCT_IMAGE_ID)
            ->willReturn(new StubProductImage());

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testResizeValidDataWithoutPassedDimensions()
    {
        $message = new Message();
        $message->setBody(JSON::encode([
            'productImageId' => self::PRODUCT_IMAGE_ID,
            'force'          => self::FORCE_OPTION
        ]));

        $image = $this->getImageMock();
        $this->imageResizeManager->expects(self::exactly(3))
            ->method('applyFilter')
            ->withConsecutive(
                [$image, self::ORIGINAL, false],
                [$image, self::LARGE, false],
                [$image, self::SMALL, false]
            );

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testResizeValidDataWithPassedNullDimensions()
    {
        $message = new Message();
        $message->setBody(JSON::encode([
            'productImageId' => self::PRODUCT_IMAGE_ID,
            'force'          => self::FORCE_OPTION,
            'dimensions'     => null
        ]));

        $image = $this->getImageMock();
        $this->imageResizeManager->expects(self::exactly(3))
            ->method('applyFilter')
            ->withConsecutive(
                [$image, self::ORIGINAL, false],
                [$image, self::LARGE, false],
                [$image, self::SMALL, false]
            );

        $this->processor->process($message, $this->getSession());
    }

    public function testResizeValidDataWithPassedDimensions()
    {
        $message = new Message();
        $message->setBody(JSON::encode([
            'productImageId' => self::PRODUCT_IMAGE_ID,
            'force'          => self::FORCE_OPTION,
            'dimensions'     => [self::ORIGINAL, self::SMALL]
        ]));

        $image = $this->getImageMock();
        $this->imageResizeManager->expects(self::exactly(2))
            ->method('applyFilter')
            ->withConsecutive(
                [$image, self::ORIGINAL, false],
                [$image, self::SMALL, false]
            );

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }
}
