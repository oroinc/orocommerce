<?php

namespace Oro\Bundle\WebCatalogBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

/**
 * The matcher for {@see \Oro\Bundle\WebCatalogBundle\Provider\ContentNodeProvider}.
 * This logic was moved to a separate class to be able to implement lazy matching algorithm
 * that requires a state.
 */
class MatcherForContentNodeProvider
{
    /** @var array [node id => [scope id, ...], ...] */
    private $scopesToMatch;

    /** @var ScopeCriteria */
    private $criteria;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ScopeManager */
    private $scopeManager;

    /** @var string */
    private $scopeType;

    /** @var Scope[] */
    private $scopes;

    /** @var bool[] [scope id => match result, ...] */
    private $matchedScopes = [];

    /**
     * @param array          $scopesToMatch [node id => [scope id, ...], ...]
     * @param ScopeCriteria  $criteria
     * @param DoctrineHelper $doctrineHelper
     * @param ScopeManager   $scopeManager
     * @param string         $scopeType
     */
    public function __construct(
        array $scopesToMatch,
        ScopeCriteria $criteria,
        DoctrineHelper $doctrineHelper,
        ScopeManager $scopeManager,
        string $scopeType
    ) {
        $this->scopesToMatch = $scopesToMatch;
        $this->criteria = $criteria;
        $this->doctrineHelper = $doctrineHelper;
        $this->scopeManager = $scopeManager;
        $this->scopeType = $scopeType;
    }

    public function isContentNodeMatchCriteria(int $nodeId): bool
    {
        if (!isset($this->scopesToMatch[$nodeId])) {
            return true;
        }

        $nodeScopes = $this->getContentNodeScopes($nodeId);
        foreach ($nodeScopes as $scope) {
            $scopeId = $scope->getId();
            if (!isset($this->matchedScopes[$scopeId])) {
                $this->matchedScopes[$scopeId] = $this->scopeManager->isScopeMatchCriteria(
                    $scope,
                    $this->criteria,
                    $this->scopeType
                );
            }
            if ($this->matchedScopes[$scopeId]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $nodeId
     *
     * @return Scope[]
     */
    private function getContentNodeScopes(int $nodeId): array
    {
        if (null === $this->scopes) {
            $this->scopes = $this->loadScopes();
        }

        $nodeScopes = [];
        foreach ($this->scopesToMatch[$nodeId] as $scopeId) {
            $scope = $this->scopes[$scopeId] ?? null;
            if (null !== $scope) {
                $nodeScopes[] = $scope;
            }
        }

        return $nodeScopes;
    }

    /**
     * @return Scope[] [scope id => scope, ...]
     */
    private function loadScopes(): array
    {
        /** @var Scope[] $scopes */
        $scopes = $this->doctrineHelper
            ->createQueryBuilder(Scope::class, 'scope')
            ->where('scope.id IN (:scopeIds)')
            ->setParameter('scopeIds', $this->getScopeIds())
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($scopes as $scope) {
            $result[$scope->getId()] = $scope;
        }

        return $result;
    }

    /**
     * @return int[]
     */
    private function getScopeIds(): array
    {
        $scopeMap = [];
        foreach ($this->scopesToMatch as $nodeId => $scopeIds) {
            foreach ($scopeIds as $scopeId) {
                if (!array_key_exists($scopeId, $scopeMap)) {
                    $scopeMap[$scopeId] = null;
                }
            }
        }

        return array_keys($scopeMap);
    }
}
