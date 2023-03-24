<?php

namespace Oro\Bundle\ProductBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic class for reindex products by attribute ids
 */
class ReindexProductsByAttributesTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public const ATTRIBUTE_IDS_OPTION = 'attributeIds';
    public const NAME = 'oro_product.reindex_products_by_attributes';

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return 'Reindex products by attribute ids';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([self::ATTRIBUTE_IDS_OPTION])
            ->setAllowedTypes(self::ATTRIBUTE_IDS_OPTION, 'int[]');
    }

    public function createJobName($messageBody): string
    {
        return self::getName();
    }
}
