<?php

namespace Oro\Bundle\ProductBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Manager\ImageResizeManagerInterface;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\ProductBundle\Async\Topic\ResizeProductImageTopic;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Provider\ProductImagesDimensionsProvider;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Generates images of all available dimensions for specified product image.
 */
class ResizeProductImageMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private ManagerRegistry $doctrine;

    private ProductImagesDimensionsProvider $imageDimensionsProvider;

    private ImageResizeManagerInterface $imageResizeManager;

    public function __construct(
        ManagerRegistry $doctrine,
        ProductImagesDimensionsProvider $imageDimensionsProvider,
        ImageResizeManagerInterface $imageResizeManager
    ) {
        $this->doctrine = $doctrine;
        $this->imageDimensionsProvider = $imageDimensionsProvider;
        $this->imageResizeManager = $imageResizeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $data = $message->getBody();

        /** @var ProductImage $productImage */
        if (!$productImage = $this->getProductImage($data['productImageId'])) {
            return self::REJECT;
        }

        $image = $productImage->getImage();
        if (!$image) {
            return self::REJECT;
        }

        foreach ($this->getApplicableFilters($productImage, $data['dimensions']) as $filterName) {
            $this->imageResizeManager->applyFilter($image, $filterName, '', $data['force']);
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics(): array
    {
        return [ResizeProductImageTopic::getName()];
    }

    private function getProductImage(int $id): ?ProductImage
    {
        return $this->doctrine->getManagerForClass(ProductImage::class)->find(ProductImage::class, $id);
    }

    /**
     * @param ProductImage  $productImage
     * @param string[]|null $dimensions
     *
     * @return string[]
     */
    private function getApplicableFilters(ProductImage $productImage, ?array $dimensions): array
    {
        $productApplicableDimensions = array_map(
            static function (ThemeImageTypeDimension $dimension) {
                return $dimension->getName();
            },
            $this->imageDimensionsProvider->getDimensionsForProductImage($productImage)
        );

        if ($dimensions) {
            return array_intersect($productApplicableDimensions, $dimensions);
        }

        return $productApplicableDimensions;
    }
}
