<?php

namespace Oro\Bundle\WebCatalogBundle\ContentNodeUtils;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Entity\ScopeCollectionAwareInterface;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\WebCatalogRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class ScopeMatcher
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var ScopeManager
     */
    private $scopeManager;

    /**
     * @param ManagerRegistry $registry
     * @param ScopeManager $scopeManager
     */
    public function __construct(ManagerRegistry $registry, ScopeManager $scopeManager)
    {
        $this->registry = $registry;
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param Collection $scopes
     * @param Scope $requiredScope
     * @return bool|int
     */
    public function getMatchingScopePriority(Collection $scopes, Scope $requiredScope)
    {
        $matchingScopes = $this->getMatchingScopes($requiredScope);

        return $this->getMatchingScopePriorityByMatchingScopes($scopes, $matchingScopes);
    }

    /**
     * @param Scope $requiredScope
     * @return array|Scope[]
     */
    public function getMatchingScopes(Scope $requiredScope)
    {
        $scopeCriteria = $this->scopeManager->getCriteriaByScope($requiredScope, 'web_content');
        $criteriaArray = $scopeCriteria->toArray();
        if (!array_key_exists('webCatalog', $criteriaArray)) {
            return [];
        }

        return $this->getWebCatalogRepository()
            ->getMatchingScopes($criteriaArray['webCatalog'], $scopeCriteria);
    }

    /**
     * @param WebCatalog $webCatalog
     * @return array|Scope[]
     */
    public function getUsedScopes(WebCatalog $webCatalog)
    {
        return $this->getWebCatalogRepository()->getUsedScopes($webCatalog);
    }

    /**
     * @param Collection|ScopeCollectionAwareInterface[] $collection
     * @param Scope $requiredScope
     * @return ScopeCollectionAwareInterface
     */
    public function getBestMatchByScope(Collection $collection, Scope $requiredScope)
    {
        $rankedData = [];
        $matchingScopes = $this->getMatchingScopes($requiredScope);
        foreach ($collection as $item) {
            $priority = $this->getMatchingScopePriorityByMatchingScopes($item->getScopes(), $matchingScopes);
            if (false !== $priority) {
                $rankedData[$priority] = $item;
            }
        }
        ksort($rankedData);

        return reset($rankedData);
    }

    /**
     * @return WebCatalogRepository
     */
    private function getWebCatalogRepository()
    {
        return $this->registry
            ->getManagerForClass(WebCatalog::class)
            ->getRepository(WebCatalog::class);
    }

    /**
     * @param Collection $scopes
     * @param array|\Traversable $matchingScopes
     * @return bool|int|string
     */
    private function getMatchingScopePriorityByMatchingScopes(Collection $scopes, $matchingScopes)
    {
        foreach ($matchingScopes as $scopeRank => $matchingScope) {
            if ($scopes->contains($matchingScope)) {
                return $scopeRank;
            }
        }

        return false;
    }
}
