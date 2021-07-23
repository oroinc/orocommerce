<?php

namespace Oro\Bundle\ProductBundle\Async;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Manager\ImageResizeManagerInterface;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
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
class ResizeProductImageMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ProductImagesDimensionsProvider */
    private $imageDimensionsProvider;

    /** @var ImageResizeManagerInterface */
    private $imageResizeManager;

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
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = $this->getMessageData($message);

        /** @var ProductImage $productImage */
        if (!$productImage = $this->getProductImage($data['productImageId'])) {
            return self::REJECT;
        }

        $image = $productImage->getImage();
        if (!$image) {
            return self::REJECT;
        }

        foreach ($this->getApplicableFilters($productImage, $data['dimensions']) as $filterName) {
            $this->imageResizeManager->applyFilter($image, $filterName, $data['force']);
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::PRODUCT_IMAGE_RESIZE];
    }

    private function getMessageData(MessageInterface $message): array
    {
        try {
            $body = JSON::decode($message->getBody());

            return $this->getOptionsResolver()->resolve((array)$body);
        } catch (OptionsResolverInvalidArgumentException|\InvalidArgumentException $e) {
            throw new MessageQueueInvalidArgumentException($e->getMessage(), $e->getCode());
        }
    }

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
