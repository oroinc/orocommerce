<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Processor topic for persisting suggestion product relations to database
 */
class PersistProductsSuggestionRelationChunkTopic extends AbstractTopic
{
    public const PRODUCTS_WRAPPER = 'product_ids_by_suggestion_id';

    private const NAME = 'oro_website_search_suggestion.persist_product_suggestions';

    #[\Override]
    public static function getName(): string
    {
        return static::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return "Persist to database products suggestions relation records";
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(static::PRODUCTS_WRAPPER)
            ->setAllowedTypes(static::PRODUCTS_WRAPPER, 'array');
    }
}
