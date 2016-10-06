<?php

namespace Oro\Bundle\ProductBundle\MessageProcessor;

use Doctrine\ORM\EntityRepository;

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
     * @param EntityRepository $imageRepository
     * @param ImageFilterLoader $filterLoader
     * @param ImageTypeProvider $imageTypeProvider
     * @param ImageResizer $imageResizer
     */
    public function __construct(
        EntityRepository $imageRepository,
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
            $this->imageResizer->resizeImage($productImage->getImage(), $dimension->getName(), $data['force']);
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
