<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\Repository\SearchTermRepository;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;

/**
 * Provides methods to find search terms
 */
class SearchTermProvider
{
    /**
     * @var int Minimum length of a search phrase to trigger the partial match search.
     */
    private int $partialMatchMinLength = 3;

    public function __construct(
        private ScopeManager $scopeManager,
        private ManagerRegistry $doctrine
    ) {
    }

    /**
     * @param int $partialMatchMinLength Minimum length of a search phrase to trigger the partial match search.
     */
    public function setPartialMatchMinLength(int $partialMatchMinLength): void
    {
        $this->partialMatchMinLength = $partialMatchMinLength;
    }

    public function getMostSuitableSearchTerm(string $search): ?SearchTerm
    {
        $criteria = $this->scopeManager->getCriteria('website_search_term');

        $searchTermRepository = $this->getRepository();
        $scopes = $searchTermRepository->findMostSuitableUsedScopes($criteria);
        $searchTerm = $searchTermRepository->findSearchTermByScopes($search, $scopes);
        if ($searchTerm) {
            return $searchTerm;
        }

        if (mb_strlen($search) > $this->partialMatchMinLength) {
            // Exact match search returned no results. So we should try to find a Search Term with partial match.
            return $searchTermRepository->findSearchTermWithPartialMatchByScopes($search, $scopes);
        }

        return null;
    }

    private function getRepository(): SearchTermRepository
    {
        return $this->doctrine->getRepository(SearchTerm::class);
    }
}
