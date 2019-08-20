<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\MessageProcessor;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\MediaCacheManager;
use Oro\Bundle\AttachmentBundle\Resizer\ImageResizer;
use Oro\Bundle\LayoutBundle\Loader\ImageFilterLoader;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\MessageProcessor\ImageResizeMessageProcessor;
use Oro\Bundle\ProductBundle\Provider\ProductImagesDimensionsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

abstract class AbstractImageResizeMessageProcessorTest extends \PHPUnit\Framework\TestCase
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
     * @var ImageFilterLoader
     */
    protected $filterLoader;

    /**
     * @var ProductImagesDimensionsProvider
     */
    protected $imageDimensionsProvider;

    /**
     * @var ImageResizer
     */
    protected $imageResizer;

    /**
     * @var AttachmentManager
     */
    protected $attachmentManager;

    /**
     * @var MediaCacheManager
     */
    protected $mediaCacheManager;

    public function setUp()
    {
        $this->imageRepository = $this->prophesize(EntityRepository::class);
        $this->filterLoader = $this->prophesize(ImageFilterLoader::class);
        $this->imageDimensionsProvider = $this->prophesize(ProductImagesDimensionsProvider::class);
        $this->imageResizer = $this->prophesize(ImageResizer::class);
        $this->attachmentManager = $this->prophesize(AttachmentManager::class);
        $this->mediaCacheManager = $this->prophesize(MediaCacheManager::class);

        $this->processor = new ImageResizeMessageProcessor(
            $this->imageRepository->reveal(),
            $this->filterLoader->reveal(),
            $this->imageDimensionsProvider->reveal(),
            $this->imageResizer->reveal(),
            $this->mediaCacheManager->reveal(),
            $this->attachmentManager->reveal()
        );
    }

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

    /**
     * @param string $body
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

    protected function prepareDependencies($image)
    {
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
                    'additional' => new ThemeImageTypeDimension(self::SMALL, 50, 50)
                ]
            );

        $this->filterLoader->load()->shouldBeCalled();
        $this->imageRepository->find(self::PRODUCT_IMAGE_ID)->willReturn($productImage->reveal());
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    protected function getImage(): \Prophecy\Prophecy\ObjectProphecy
    {
        $image = $this->prophesize(File::class);
        $image->getId()->willReturn(self::PRODUCT_IMAGE_ID);
        $image->getId()->willReturn(null);

        return $image;
    }
}
