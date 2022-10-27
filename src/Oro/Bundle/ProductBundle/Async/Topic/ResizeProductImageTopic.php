<?php

namespace Oro\Bundle\ProductBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic class for resizing product images by product id using received dimensions
 */
class ResizeProductImageTopic extends AbstractTopic
{
    public const PRODUCT_IMAGE_ID_OPTION = 'productImageId';
    public const FORCE_OPTION = 'force';
    public const DIMENSIONS_OPTION = 'dimensions';

    public static function getName(): string
    {
        return 'oro_product.image_resize';
    }

    public static function getDescription(): string
    {
        return 'Topic for resizing product images by product id using received dimensions';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->define(self::PRODUCT_IMAGE_ID_OPTION)
            ->allowedTypes('int')
            ->required();

        $resolver
            ->define(self::FORCE_OPTION)
            ->allowedTypes('bool')
            ->default(false);

        $resolver
            ->define(self::DIMENSIONS_OPTION)
            ->allowedTypes('array', 'null')
            ->default([]);
    }
}
