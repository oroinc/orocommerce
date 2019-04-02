<?php

namespace Oro\Bundle\ProductBundle\MessageProcessor;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\MediaCacheManager;
use Oro\Bundle\AttachmentBundle\Resizer\ImageResizer;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\EventListener\ProductImageResizeListener;
use Oro\Bundle\ProductBundle\Provider\ProductImagesDimensionsProvider;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * Generates images of all available dimensions for specified product image.
 */
class ImageResizeMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var EntityRepository
     */
    protected $imageRepository;

    /**
     * @var ProductImagesDimensionsProvider
     */
    protected $imageDimensionsProvider;

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
     * @param ProductImagesDimensionsProvider $imageDimensionsProvider
     * @param ImageResizer $imageResizer
     * @param MediaCacheManager $mediaCacheManager
     * @param AttachmentManager $attachmentManager
     */
    public function __construct(
        EntityRepository $imageRepository,
        ProductImagesDimensionsProvider $imageDimensionsProvider,
        ImageResizer $imageResizer,
        MediaCacheManager $mediaCacheManager,
        AttachmentManager $attachmentManager
    ) {
        $this->imageRepository = $imageRepository;
        $this->imageDimensionsProvider = $imageDimensionsProvider;
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

        foreach ($this->imageDimensionsProvider->getDimensionsForProductImage($productImage) as $dimension) {
            $image = $productImage->getImage();
            $filterName = $dimension->getName();
            $imagePath = $this->attachmentManager->getFilteredImageUrl($image, $filterName);

            if (!$data['force'] && $this->mediaCacheManager->exists($imagePath)) {
                continue;
            }

            if ($filteredImage = $this->imageResizer->resizeImage($image, $filterName)) {
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
}
