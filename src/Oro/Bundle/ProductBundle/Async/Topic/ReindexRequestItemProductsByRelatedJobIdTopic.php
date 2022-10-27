<?php

namespace Oro\Bundle\ProductBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Dispatches search reindexation event for all records that found by given relatedJobId.
 */
class ReindexRequestItemProductsByRelatedJobIdTopic extends AbstractTopic
{
    public const NAME = 'oro_product.reindex_request_item_products_by_related_job';

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return 'Dispatches search reindexation event for all records that found by given relatedJobId.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver->setDefault('indexationFieldsGroups', null);
        $resolver->setRequired([
            'relatedJobId'
        ]);

        $resolver->setAllowedTypes('relatedJobId', ['int']);
        $resolver->setAllowedTypes('indexationFieldsGroups', ['string[]', 'null']);
    }
}
