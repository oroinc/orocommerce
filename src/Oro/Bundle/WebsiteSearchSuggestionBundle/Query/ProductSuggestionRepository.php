<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Query;

use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Engine\EngineParameters;
use Oro\Bundle\SearchBundle\Engine\Orm;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchRepository;

/**
 * Website search engine repository for OroProductBundle:ProductSuggestion entity
 * This repository encapsulates ProductSuggestion related operations
 */
class ProductSuggestionRepository extends WebsiteSearchRepository
{
    private EngineParameters $engineParameters;

    private ProductRepository $productRepository;

    public function setEngineParameters(EngineParameters $engineParameters): void
    {
        $this->engineParameters = $engineParameters;
    }

    public function setProductRepository(ProductRepository $productRepository): void
    {
        $this->productRepository = $productRepository;
    }

    public function getAutocompleteSuggestsSearchQuery(
        string $queryString,
        int $localizationId,
        int $maxNumber
    ): SearchQueryInterface {
        $operator = $this->productRepository->getProductSearchOperator();

        $query = $this->createQuery()
            ->addSelect('text.phrase')
            ->setFrom('oro_website_search_suggestion_WEBSITE_ID')
            ->addWhere(Criteria::expr()->eq('integer.localization_id', $localizationId))
            ->addWhere(Criteria::expr()->neq('text.phrase', $queryString))
            ->setMaxResults($maxNumber);

        if ($this->engineParameters->getEngineName() === Orm::ENGINE_NAME) {
            $words = explode(' ', $queryString);
            foreach ($words as $word) {
                $query->addWhere(Criteria::expr()->$operator('text.phrase', $word));
            }
            $query->setOrderBy('integer.words_count');
        } else {
            $query->addWhere(Criteria::expr()->$operator('text.phrase', $queryString));
            $query->setOrderBy('integer.id');
        }

        return $query;
    }
}
