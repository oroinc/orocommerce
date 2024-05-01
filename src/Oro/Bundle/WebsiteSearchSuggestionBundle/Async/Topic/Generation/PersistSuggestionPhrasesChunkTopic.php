<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Processor topic for persisting suggestion phrases to database
 */
class PersistSuggestionPhrasesChunkTopic extends AbstractTopic
{
    public const ORGANIZATION = GenerateSuggestionsPhrasesChunkTopic::ORGANIZATION;

    public const PRODUCTS_WRAPPER = 'product_ids_by_localization_id';

    private const NAME = 'oro_website_search_suggestion.persist_suggestions';

    public static function getName(): string
    {
        return static::NAME;
    }

    public static function getDescription(): string
    {
        return "Persist to database generated phrases";
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(static::PRODUCTS_WRAPPER)
            ->setAllowedTypes(static::PRODUCTS_WRAPPER, 'array');

        $resolver
            ->setRequired(static::ORGANIZATION)
            ->setAllowedTypes(static::ORGANIZATION, ['string', 'int']);
    }
}
