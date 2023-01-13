<?php

namespace Oro\Bundle\WebsiteSearchBundle\Async\Topic;

use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to add to the website search index the specified entities by class and ids.
 */
class WebsiteSearchSaveTopic extends AbstractTopic
{
    private IndexerInputValidator $indexerInputValidator;

    public function __construct(IndexerInputValidator $indexerInputValidator)
    {
        $this->indexerInputValidator = $indexerInputValidator;
    }

    public static function getName(): string
    {
        return 'oro.website.search.indexer.save';
    }

    public static function getDescription(): string
    {
        return 'Add to the website search index the specified entities by class and ids.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $this->indexerInputValidator->configureEntityOptions($resolver);
        $this->indexerInputValidator->configureContextOptions($resolver);
    }
}
