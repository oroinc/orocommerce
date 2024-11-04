<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for generation phrases for provided products
 */
class GenerateSuggestionsPhrasesChunkTopic extends AbstractTopic
{
    public const ORGANIZATION = 'organization';

    private const NAME = 'oro_website_search_suggestion.generate_for_products';

    #[\Override]
    public static function getName(): string
    {
        return static::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return "Generate suggestion phrases for provided chunk of products";
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(GenerateSuggestionsTopic::PRODUCT_IDS)
            ->setAllowedTypes(GenerateSuggestionsTopic::PRODUCT_IDS, ['string[]', 'int[]'])
            ->setAllowedValues(GenerateSuggestionsTopic::PRODUCT_IDS, fn (array $ids) => !empty($ids));

        $resolver
            ->setRequired(self::ORGANIZATION)
            ->setAllowedTypes(self::ORGANIZATION, ['string', 'int']);
    }
}
