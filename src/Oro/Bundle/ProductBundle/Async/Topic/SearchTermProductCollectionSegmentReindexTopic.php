<?php

namespace Oro\Bundle\ProductBundle\Async\Topic;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * MQ topic to initiate reindex of the product collection segment of the specified {@see SearchTerm} entity.
 */
class SearchTermProductCollectionSegmentReindexTopic extends AbstractTopic
{
    public const SEARCH_TERM_ID = 'search_term_id';
    public const SEARCH_TERM = 'search_term';

    public function __construct(private readonly ManagerRegistry $doctrine)
    {
    }

    public static function getName(): string
    {
        return 'oro_product.search_term_product_collection_segment_reindex';
    }

    public static function getDescription(): string
    {
        return 'Initiates reindex of the product collection segment of the persisted/updated {@see SearchTerm} entity.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->define(self::SEARCH_TERM_ID)
            ->required()
            ->allowedTypes('int');

        $resolver
            ->define(self::SEARCH_TERM)
            ->allowedTypes('null', SearchTerm::class)
            ->default(function (Options $options, $previousValue) {
                if ($previousValue instanceof SearchTerm) {
                    return $previousValue;
                }

                $searchTerm = $this->doctrine->getRepository(SearchTerm::class)->find($options[self::SEARCH_TERM_ID]);
                if (!$searchTerm instanceof SearchTerm) {
                    throw new InvalidOptionsException(
                        'Search Term #' . $options[self::SEARCH_TERM_ID] . ' is not found'
                    );
                }

                return $searchTerm;
            });
    }
}
