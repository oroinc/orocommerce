<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\MessageProcessor;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\ImageResizeManagerInterface;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\MessageProcessor\ImageResizeMessageProcessor;
use Oro\Bundle\ProductBundle\Provider\ProductImagesDimensionsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
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
     * @var EntityRepository
     */
    protected $imageRepository;

    /**
     * @var ProductImagesDimensionsProvider
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
        $this->imageRepository = $this->prophesize(EntityRepository::class);
        $this->imageDimensionsProvider = $this->prophesize(ProductImagesDimensionsProvider::class);
        $this->imageResizeManager = $this->prophesize(ImageResizeManagerInterface::class);

        $this->processor = new ImageResizeMessageProcessor(
            $this->imageRepository->reveal(),
            $this->imageDimensionsProvider->reveal(),
            $this->imageResizeManager->reveal()
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
     * @return DbalMessage
     */
    protected function prepareValidMessage()
    {
        return $this->prepareMessage(JSON::encode(self::$validData));
    }

    protected function prepareDependencies()
    {
        $image = $this->prophesize(File::class);
        $image->getId()->willReturn(self::PRODUCT_IMAGE_ID);
        $image->getId()->willReturn(null);

        $productImage = $this->prophesize(StubProductImage::class);
        $productImage->getImage()->willReturn($image->reveal());
        $productImage->getTypes()->willReturn([
            'main' => new ProductImageType('main'),
            'listing' => new ProductImageType('listing'),
        ]);
        $productImage->getId()->willReturn(self::PRODUCT_IMAGE_ID);

        $this->imageDimensionsProvider->getDimensionsForProductImage($productImage)
            ->willReturn(
                [
                    'main' => new ThemeImageTypeDimension(self::ORIGINAL, null, null),
                    'listing' => new ThemeImageTypeDimension(self::LARGE, 100, 100),
                    'additional' => new ThemeImageTypeDimension(self::ORIGINAL, null, null)
                ]
            );

        $this->imageRepository->find(self::PRODUCT_IMAGE_ID)->willReturn($productImage->reveal());

        $this->imageResizeManager->applyFilter($image, self::ORIGINAL, false);
        $this->imageResizeManager->applyFilter($image, self::LARGE, false);
        $this->imageResizeManager->applyFilter($image, self::SMALL, false);
    }

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
