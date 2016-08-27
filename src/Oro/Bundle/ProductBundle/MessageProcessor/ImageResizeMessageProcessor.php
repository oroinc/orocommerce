<?php

namespace Oro\Bundle\ProductBundle\MessageProcessor;

use Oro\Bundle\AttachmentBundle\Resizer\ImageResizer;
use Oro\Bundle\LayoutBundle\Loader\ImageFilterLoader;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductImageRepository;
use Oro\Bundle\ProductBundle\EventListener\ProductImageResizeListener;

use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class ImageResizeMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var ProductImageRepository
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
     * @param ProductImageRepository $imageRepository
     * @param ImageFilterLoader $filterLoader
     * @param ImageTypeProvider $imageTypeProvider
     * @param ImageResizer $imageResizer
     */
    public function __construct(
        ProductImageRepository $imageRepository,
        ImageFilterLoader $filterLoader,
        ImageTypeProvider $imageTypeProvider,
        ImageResizer $imageResizer
    ) {

        $this->imageRepository = $imageRepository;
        $this->filterLoader = $filterLoader;
        $this->imageTypeProvider = $imageTypeProvider;
        $this->imageResizer = $imageResizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = json_decode($message->getBody(), true);

        if (!$data || count($data) != 2) {
            return self::REJECT;
        }

        list($productImageId, $forceOption) = $data;

        /** @var ProductImage $productImage */
        if (!$productImage = $this->imageRepository->find($productImageId)) {
            return self::REJECT;
        }

        $this->filterLoader->load();

        foreach ($this->getDimensionsForProductImage($productImage) as $dimension) {
            $this->imageResizer->resizeImage($productImage->getImage(), $dimension->getName(), $forceOption);
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
    private function getDimensionsForProductImage(ProductImage $productImage)
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
