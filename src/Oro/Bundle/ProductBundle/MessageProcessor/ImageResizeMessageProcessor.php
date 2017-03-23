<?php

namespace Oro\Bundle\ProductBundle\MessageProcessor;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\MediaCacheManager;
use Oro\Bundle\AttachmentBundle\Resizer\ImageResizer;
use Oro\Bundle\LayoutBundle\Loader\ImageFilterLoader;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\EventListener\ProductImageResizeListener;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class ImageResizeMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
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
     * @var MediaCacheManager
     */
    private $mediaCacheManager;

    /**
     * @var AttachmentManager
     */
    private $attachmentManager;

    /**
     * @param EntityRepository $imageRepository
     * @param ImageFilterLoader $filterLoader
     * @param ImageTypeProvider $imageTypeProvider
     * @param ImageResizer $imageResizer
     * @param MediaCacheManager $mediaCacheManager
     * @param AttachmentManager $attachmentManager
     */
    public function __construct(
        EntityRepository $imageRepository,
        ImageFilterLoader $filterLoader,
        ImageTypeProvider $imageTypeProvider,
        ImageResizer $imageResizer,
        MediaCacheManager $mediaCacheManager,
        AttachmentManager $attachmentManager
    ) {

        $this->imageRepository = $imageRepository;
        $this->filterLoader = $filterLoader;
        $this->imageTypeProvider = $imageTypeProvider;
        $this->imageResizer = $imageResizer;
        $this->mediaCacheManager = $mediaCacheManager;
        $this->attachmentManager = $attachmentManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        try {
            $data = array_replace(
                ['productImageId' => null, 'force' => null],
                JSON::decode($message->getBody())
            );
        } catch (\InvalidArgumentException $e) {
            return self::REJECT;
        }

        if (!is_int($data['productImageId']) || !is_bool($data['force'])) {
            return self::REJECT;
        }

        /** @var ProductImage $productImage */
        if (!$productImage = $this->imageRepository->find($data['productImageId'])) {
            return self::REJECT;
        }

        $this->filterLoader->load();

        foreach ($this->getDimensionsForProductImage($productImage) as $dimension) {
            $imagePath = $this->attachmentManager->getFilteredImageUrl($productImage->getImage(), $dimension->getName());
            if (!$data['force'] && $this->mediaCacheManager->exists($imagePath)) {
                continue;
            }

            if ($filteredImage = $this->imageResizer->resizeImage($productImage->getImage(), $dimension->getName())) {
                $this->mediaCacheManager->store($filteredImage->getContent(), $imagePath);
            }
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [ProductImageResizeListener::IMAGE_RESIZE_TOPIC];
    }

    /**
     * @param ProductImage $productImage
     * @return ThemeImageTypeDimension[]
     */
    protected function getDimensionsForProductImage(ProductImage $productImage)
    {
        $dimensions = [];
        $allImageTypes = $this->imageTypeProvider->getImageTypes();

        foreach ($productImage->getTypes() as $imageType) {
            if (isset($allImageTypes[$imageType])) {
                $dimensions = array_merge($dimensions, $allImageTypes[$imageType]->getDimensions());
            }
        }

        return $dimensions;
    }
}
