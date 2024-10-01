<?php

namespace Oro\Bundle\WebsiteSearchBundle\Async\Topic;

use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to reindex the specified entities by class and ids.
 */
class WebsiteSearchReindexGranulizedTopic extends AbstractTopic
{
    private IndexerInputValidator $indexerInputValidator;

    public function __construct(IndexerInputValidator $indexerInputValidator)
    {
        $this->indexerInputValidator = $indexerInputValidator;
    }

    #[\Override]
    public static function getName(): string
    {
        return 'oro.website.search.indexer.reindex_granulized';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Reindex the specified entities by class and ids.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $this->indexerInputValidator->configureClassOptions($resolver);
        $this->indexerInputValidator->configureContextOptions($resolver);
    }
}
