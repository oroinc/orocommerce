<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Deletion;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic class which deletes product suggestions.
 */
class DeleteOrphanSuggestionsChunkTopic extends AbstractTopic
{
    public const SUGGESTION_IDS = 'suggestion_ids';

    private const NAME = 'oro_website_search_suggestion.delete_orphan_suggestions_chunk';

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return "Execute delete operations for orphan suggestion ids.";
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(self::SUGGESTION_IDS)
            ->setAllowedTypes(self::SUGGESTION_IDS, ['string[]', 'int[]'])
            ->setAllowedValues(self::SUGGESTION_IDS, fn (array $value) => !empty($value));
    }
}
