<?php

namespace Oro\Bundle\ProductBundle\MessageProcessor;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AttachmentBundle\Manager\ImageResizeManagerInterface;
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
     * @var ImageResizeManagerInterface
     */
    private $imageResizeManager;

    /**
     * @param EntityRepository $imageRepository
     * @param ProductImagesDimensionsProvider $imageDimensionsProvider
     * @param ImageResizeManagerInterface $imageResizeManager
     */
    public function __construct(
        EntityRepository $imageRepository,
        ProductImagesDimensionsProvider $imageDimensionsProvider,
        ImageResizeManagerInterface $imageResizeManager
    ) {
        $this->imageRepository = $imageRepository;
        $this->imageDimensionsProvider = $imageDimensionsProvider;
        $this->imageResizeManager = $imageResizeManager;
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
            $this->imageResizeManager->applyFilter($productImage->getImage(), $dimension->getName(), $data['force']);
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
