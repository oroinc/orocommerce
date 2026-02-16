<?php

namespace Oro\Bundle\WebCatalogBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * The provider of the scope and the scope criteria for the current storefront request.
 */
class RequestWebContentScopeProvider
{
    private const REQUEST_SCOPE_ATTRIBUTE    = '_web_content_scope';
    private const REQUEST_SCOPES_ATTRIBUTE = '_web_content_scopes';
    private const REQUEST_CRITERIA_ATTRIBUTE = '_web_content_criteria';

    private FrontendHelper $frontendHelper;

    private string $apiPrefix;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ManagerRegistry $doctrine,
        private readonly ScopeManager $scopeManager,
        private readonly MatchedUrlDecisionMaker $matchedUrlDecisionMaker,
    ) {
    }

    public function setFrontendHelper(FrontendHelper $frontendHelper): void
    {
        $this->frontendHelper = $frontendHelper;
    }

    public function setApiPrefix(string $apiPrefix): void
    {
        $this->apiPrefix = $apiPrefix;
    }

    public function getScope(): ?Scope
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return null;
        }

        if ($request->attributes->has(self::REQUEST_SCOPE_ATTRIBUTE)) {
            return $request->attributes->get(self::REQUEST_SCOPE_ATTRIBUTE);
        }

        $scope = null;
        $criteria = $this->getRequestScopeCriteria($request);
        if (null !== $criteria) {
            $scope = $this->getSlugRepository()->findMostSuitableUsedScope($criteria);
        }
        $request->attributes->set(self::REQUEST_SCOPE_ATTRIBUTE, $scope);

        return $scope;
    }

    public function getScopes(): ?array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return null;
        }

        if ($request->attributes->has(self::REQUEST_SCOPES_ATTRIBUTE)) {
            return $request->attributes->get(self::REQUEST_SCOPES_ATTRIBUTE);
        }

        $scopes = [];
        $criteria = $this->getRequestScopeCriteria($request);
        if (null !== $criteria) {
            $scopes = $this->getSlugRepository()->findMostSuitableUsedScopes($criteria);
        }
        $request->attributes->set(self::REQUEST_SCOPES_ATTRIBUTE, $scopes);

        return $scopes;
    }

    public function getScopeCriteria(): ?ScopeCriteria
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return null;
        }

        return $this->getRequestScopeCriteria($request);
    }

    private function getRequestScopeCriteria(Request $request): ?ScopeCriteria
    {
        if ($request->attributes->has(self::REQUEST_CRITERIA_ATTRIBUTE)) {
            return $request->attributes->get(self::REQUEST_CRITERIA_ATTRIBUTE);
        }

        $criteria = null;

        if ($this->matchedUrlDecisionMaker->matches($request->getPathInfo()) ||
            $this->isStorefrontApiUrl($request->getPathInfo()) ||
            $request->attributes->get('exception')?->getStatusCode() === Response::HTTP_NOT_FOUND
        ) {
            $criteria = $this->scopeManager->getCriteria('web_content');
        }
        $request->attributes->set(self::REQUEST_CRITERIA_ATTRIBUTE, $criteria);

        return $criteria;
    }

    private function isStorefrontApiUrl(string $pathInfo): bool
    {
        if (!isset($this->frontendHelper) || !isset($this->apiPrefix)) {
            return false;
        }

        return $this->frontendHelper->isFrontendUrl($pathInfo)
            && str_starts_with($pathInfo, $this->apiPrefix);
    }

    private function getSlugRepository(): SlugRepository
    {
        return $this->doctrine->getRepository(Slug::class);
    }
}
