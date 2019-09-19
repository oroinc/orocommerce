<?php

namespace Oro\Bundle\ProductBundle\MessageProcessor;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\MediaCacheManager;
use Oro\Bundle\AttachmentBundle\Resizer\ImageResizer;
use Oro\Bundle\LayoutBundle\Loader\ImageFilterLoader;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\EventListener\ProductImageResizeListener;
use Oro\Bundle\ProductBundle\Provider\ProductImagesDimensionsProvider;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Exception\InvalidArgumentException as MessageQueueInvalidArgumentException;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException as OptionsResolverInvalidArgumentException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
     * @param ProductImagesDimensionsProvider $imageDimensionsProvider
     * @param ImageResizer $imageResizer
     * @param MediaCacheManager $mediaCacheManager
     * @param AttachmentManager $attachmentManager
     */
    public function __construct(
        EntityRepository $imageRepository,
        ImageFilterLoader $filterLoader,
        ProductImagesDimensionsProvider $imageDimensionsProvider,
        ImageResizer $imageResizer,
        MediaCacheManager $mediaCacheManager,
        AttachmentManager $attachmentManager
    ) {
        $this->imageRepository = $imageRepository;
        $this->filterLoader = $filterLoader;
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
        $data = $this->getMessageData($message);

        /** @var ProductImage $productImage */
        if (!$productImage = $this->getProductImage($data['productImageId'])) {
            return self::REJECT;
        }

        $this->filterLoader->load();
        $image = $productImage->getImage();
        foreach ($this->getApplicableFilters($productImage, $data['dimensions']) as $filterName) {
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

    /**
     * @param MessageInterface $message
     *
     * @return array
     */
    private function getMessageData(MessageInterface $message): array
    {
        try {
            $body = JSON::decode($message->getBody());

            return $this->getOptionsResolver()->resolve((array)$body);
        } catch (OptionsResolverInvalidArgumentException|\InvalidArgumentException $e) {
            throw new MessageQueueInvalidArgumentException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @return OptionsResolver
     */
    private function getOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(['productImageId']);
        $resolver->setDefault('force', false);
        $resolver->setDefault('dimensions', []);
        $resolver->setAllowedTypes('productImageId', 'int');
        $resolver->setAllowedTypes('force', ['bool', 'null']);
        $resolver->setAllowedTypes('dimensions', ['array', 'null']);
        $resolver->setNormalizer('force', static function (Options $options, $value) {
            return (bool)$value;
        });

        return $resolver;
    }

    /**
     * @param int $id
     * @return ProductImage|null
     */
    private function getProductImage($id): ?ProductImage
    {
        return $this->imageRepository->find($id);
    }

    /**
     * @param ProductImage $productImage
     * @param array|null $dimensions
     * @return array|string[]
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
