<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Deletion;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic class for run the deleting orphan suggestions.
 */
class DeleteOrphanSuggestionsTopic extends AbstractTopic
{
    private const NAME = 'oro_website_search_suggestion.delete_orphan_suggestions';

    #[\Override]
    public static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return "Initiate delete operations for suggestions that don't have a product.";
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
    }
}
