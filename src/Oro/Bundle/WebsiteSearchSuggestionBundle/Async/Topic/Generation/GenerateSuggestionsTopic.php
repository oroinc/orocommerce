<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic used for product suggestions generation
 */
class GenerateSuggestionsTopic extends AbstractTopic
{
    public const PRODUCT_IDS = 'ids';

    private const NAME = 'oro_website_search_suggestion.generate_product_suggestions';

    #[\Override]
    public static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return "Initial product suggestions generation";
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined(self::PRODUCT_IDS)
            ->setDefault(self::PRODUCT_IDS, [])
            ->setAllowedTypes(self::PRODUCT_IDS, ['string[]', 'int[]']);
    }
}
