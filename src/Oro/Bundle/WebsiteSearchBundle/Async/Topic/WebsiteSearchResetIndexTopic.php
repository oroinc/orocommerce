<?php

namespace Oro\Bundle\WebsiteSearchBundle\Async\Topic;

use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to reset the website search index.
 */
class WebsiteSearchResetIndexTopic extends AbstractTopic
{
    private IndexerInputValidator $indexerInputValidator;

    public function __construct(IndexerInputValidator $indexerInputValidator)
    {
        $this->indexerInputValidator = $indexerInputValidator;
    }

    #[\Override]
    public static function getName(): string
    {
        return 'oro.website.search.indexer.reset_index';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Reset (clear) the entire index or a specific entity class in it.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $this->indexerInputValidator->configureClassOptions($resolver);
        $this->indexerInputValidator->configureContextOptions($resolver);
    }
}
