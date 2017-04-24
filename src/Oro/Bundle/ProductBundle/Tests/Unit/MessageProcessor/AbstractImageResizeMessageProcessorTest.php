<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\MessageProcessor;

use Doctrine\ORM\EntityRepository;

use Liip\ImagineBundle\Binary\BinaryInterface;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\MediaCacheManager;
use Oro\Bundle\AttachmentBundle\Resizer\ImageResizer;
use Oro\Bundle\LayoutBundle\Loader\ImageFilterLoader;
use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\MessageProcessor\ImageResizeMessageProcessor;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;

use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

abstract class AbstractImageResizeMessageProcessorTest extends \PHPUnit_Framework_TestCase
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
     * @var ImageTypeProvider
     */
    protected $imageTypeProvider;

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
        $this->imageTypeProvider = $this->prophesize(ImageTypeProvider::class);
        $this->imageResizer = $this->prophesize(ImageResizer::class);
        $this->attachmentManager = $this->prophesize(AttachmentManager::class);
        $this->mediaCacheManager = $this->prophesize(MediaCacheManager::class);

        $this->processor = new ImageResizeMessageProcessor(
            $this->imageRepository->reveal(),
            $this->filterLoader->reveal(),
            $this->imageTypeProvider->reveal(),
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

        $productImage = $this->prophesize(StubProductImage::class);
        $productImage->getImage()->willReturn($image->reveal());
        $productImage->getTypes()->willReturn(['main', 'listing']);
        $productImage->getId()->willReturn(self::PRODUCT_IMAGE_ID);

        $this->imageTypeProvider->getImageTypes()->willReturn([
            'main' => new ThemeImageType('name1', 'label1', [
                new ThemeImageTypeDimension(self::ORIGINAL, null, null),
                new ThemeImageTypeDimension(self::LARGE, 1000, 1000)
            ]),
            'listing' => new ThemeImageType('name2', 'label2', [
                new ThemeImageTypeDimension(self::SMALL, 100, 100),
                new ThemeImageTypeDimension(self::LARGE, 1000, 1000)
            ]),
            'additional' => new ThemeImageType('name3', 'label3', [])
        ]);

        $this->filterLoader->load()->shouldBeCalled();
        $this->imageRepository->find(self::PRODUCT_IMAGE_ID)->willReturn($productImage->reveal());

        $this->attachmentManager->getFilteredImageUrl($image, self::ORIGINAL)->willReturn(self::PATH_ORIGINAL);
        $this->attachmentManager->getFilteredImageUrl($image, self::LARGE)->willReturn(self::PATH_LARGE);
        $this->attachmentManager->getFilteredImageUrl($image, self::SMALL)->willReturn(self::PATH_SMALL);

        $this->mediaCacheManager->exists(self::PATH_ORIGINAL)->willReturn(false);
        $this->mediaCacheManager->exists(self::PATH_LARGE)->willReturn(false);
        $this->mediaCacheManager->exists(self::PATH_SMALL)->willReturn(true);

        $filteredImageOriginal = $this->prophesize(BinaryInterface::class);
        $filteredImageOriginal->getContent()->willReturn(self::CONTENT_ORIGINAL);
        $filteredImageLarge = $this->prophesize(BinaryInterface::class);
        $filteredImageLarge->getContent()->willReturn(self::CONTENT_LARGE);

        $this->imageResizer->resizeImage($image, self::ORIGINAL)->willReturn($filteredImageOriginal->reveal());
        $this->imageResizer->resizeImage($image, self::LARGE)->willReturn($filteredImageLarge->reveal());
        $this->imageResizer->resizeImage($image, self::SMALL)->shouldNotBeCalled();

        $this->mediaCacheManager->store(self::CONTENT_ORIGINAL, self::PATH_ORIGINAL)->shouldBeCalled();
        $this->mediaCacheManager->store(self::CONTENT_LARGE, self::PATH_LARGE)->shouldBeCalled();
    }
}
