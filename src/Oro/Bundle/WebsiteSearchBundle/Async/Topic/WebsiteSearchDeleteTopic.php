<?php

namespace Oro\Bundle\WebsiteSearchBundle\Async\Topic;

use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to delete from the website search index the specified entities by class and ids.
 */
class WebsiteSearchDeleteTopic extends AbstractTopic
{
    private IndexerInputValidator $indexerInputValidator;

    public function __construct(IndexerInputValidator $indexerInputValidator)
    {
        $this->indexerInputValidator = $indexerInputValidator;
    }

    #[\Override]
    public static function getName(): string
    {
        return 'oro.website.search.indexer.delete';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Delete from the website search index the specified entities by class and ids.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $this->indexerInputValidator->configureEntityOptions($resolver);
        $this->indexerInputValidator->configureContextOptions($resolver);
    }
}
