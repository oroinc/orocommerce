<?php

namespace Oro\Bundle\WebsiteSearchBundle\Manager;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\Repository\SearchResultHistoryRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Manipulates search result tracking.
 */
class SearchResultHistoryManager implements SearchResultHistoryManagerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private SearchResultHistoryRepository $repository;
    private TokenStorageInterface $tokenStorage;
    private WebsiteManager $websiteManager;
    private LocalizationHelper $localizationHelper;

    public function __construct(
        SearchResultHistoryRepository $repository,
        TokenStorageInterface $tokenStorage,
        WebsiteManager $websiteManager,
        LocalizationHelper $localizationHelper
    ) {
        $this->repository = $repository;
        $this->tokenStorage = $tokenStorage;
        $this->websiteManager = $websiteManager;
        $this->localizationHelper = $localizationHelper;
        $this->logger = new NullLogger();
    }

    public function saveSearchResult(
        string $searchTerm,
        string $searchType,
        int $resultsCount,
        string $searchSessionId = null
    ): void {
        $token = $this->tokenStorage->getToken();
        $website = $this->websiteManager->getCurrentWebsite();
        $organization = $token->getOrganization();
        $businessUnit = $this->getOwner($website, $organization);

        if (!$businessUnit) {
            $this->logger->warning('Unable to get owner for SearchResultHistory entity');

            return;
        }

        $customerVisitor = $this->getCustomerVisitor($token);
        $customerUser = $this->getCustomerUser($token);

        try {
            $this->repository->upsertSearchHistoryRecord(
                $searchTerm,
                $searchType,
                $resultsCount,
                $this->getNormalizedSearchTermHash($searchTerm),
                $businessUnit->getId(),
                $searchSessionId,
                $this->localizationHelper->getCurrentLocalization()?->getId(),
                $website?->getId(),
                $customerUser?->getCustomer()?->getId(),
                $customerUser?->getId(),
                $customerVisitor?->getId(),
                $organization?->getId()
            );
        } catch (\Exception $e) {
            $this->logger->error('Unable to save search term', ['exception' => $e]);
        }
    }

    private function getCustomerVisitor(?TokenInterface $token): ?CustomerVisitor
    {
        $customerVisitor = null;
        if ($token instanceof AnonymousCustomerUserToken) {
            $customerVisitor = $token->getVisitor();
        }

        return $customerVisitor;
    }

    private function getCustomerUser(?TokenInterface $token): ?CustomerUser
    {
        $customerUser = $token->getUser();
        if (!$customerUser instanceof CustomerUser) {
            $customerUser = null;
        }

        return $customerUser;
    }

    private function getOwner(?Website $website, ?Organization $organization): ?BusinessUnit
    {
        if ($website) {
            return $website->getOwner();
        }

        return $organization?->getBusinessUnits()->first();
    }

    private function getNormalizedSearchTermHash(string $searchTerm): string
    {
        return md5(
            mb_strtolower(
                Query::clearString($searchTerm)
            )
        );
    }
}
