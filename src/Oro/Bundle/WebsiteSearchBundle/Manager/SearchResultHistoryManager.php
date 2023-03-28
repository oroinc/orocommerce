<?php

namespace Oro\Bundle\WebsiteSearchBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\Repository\SearchResultHistoryRepository;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\Repository\SearchTermReportRepository;
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

    private SearchResultHistoryRepository $historyRepository;
    private SearchTermReportRepository $reportRepository;
    private TokenStorageInterface $tokenStorage;
    private WebsiteManager $websiteManager;
    private LocalizationHelper $localizationHelper;
    private ConfigManager $configManager;
    private int $keepDays = 30;

    public function __construct(
        SearchResultHistoryRepository $historyRepository,
        SearchTermReportRepository $reportRepository,
        TokenStorageInterface $tokenStorage,
        WebsiteManager $websiteManager,
        LocalizationHelper $localizationHelper,
        ConfigManager $configManager
    ) {
        $this->historyRepository = $historyRepository;
        $this->reportRepository = $reportRepository;
        $this->tokenStorage = $tokenStorage;
        $this->websiteManager = $websiteManager;
        $this->localizationHelper = $localizationHelper;
        $this->logger = new NullLogger();
        $this->configManager = $configManager;
    }

    public function setKeepDays(int $keepDays): void
    {
        $this->keepDays = $keepDays;
    }

    public function saveSearchResult(
        string $searchTerm,
        string $searchType,
        int $resultsCount,
        string $searchSessionId = null
    ): void {
        $token = $this->tokenStorage->getToken();
        $website = $this->websiteManager->getCurrentWebsite();
        $organization = $this->getOrganization($token);
        $businessUnit = $this->getOwner($website, $organization);

        if (!$businessUnit) {
            $this->logger->warning('Unable to get owner for SearchResultHistory entity');

            return;
        }

        $customerVisitor = $this->getCustomerVisitor($token);
        $customerUser = $this->getCustomerUser($token);

        try {
            $this->historyRepository->upsertSearchHistoryRecord(
                $searchTerm,
                $searchType,
                $resultsCount,
                $this->getNormalizedSearchTermHash($searchTerm),
                $businessUnit->getId(),
                $website->getId(),
                $searchSessionId,
                $this->localizationHelper->getCurrentLocalization()?->getId(),
                $customerUser?->getCustomer()?->getId(),
                $customerUser?->getId(),
                $customerVisitor?->getId(),
                $organization?->getId()
            );
        } catch (\Exception $e) {
            $this->logger->error('Unable to save search term', ['exception' => $e]);
        }
    }

    public function removeOutdatedHistoryRecords(): void
    {
        $this->historyRepository->removeOldRecords($this->keepDays);
    }

    public function actualizeHistoryReport(): void
    {
        foreach ($this->historyRepository->getOrganizationsByHistory() as $organization) {
            $timezone = $this->configManager->get('oro_locale.timezone', 'UTC', false, $organization);
            $this->reportRepository->actualizeReport($organization, new \DateTimeZone($timezone));
        }
    }

    private function getCustomerVisitor(?TokenInterface $token): ?CustomerVisitor
    {
        if ($token instanceof AnonymousCustomerUserToken) {
            return $token->getVisitor();
        }

        return null;
    }

    private function getCustomerUser(?TokenInterface $token): ?CustomerUser
    {
        $customerUser = $token?->getUser();
        if (!$customerUser instanceof CustomerUser) {
            return null;
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

    private function getOrganization(?TokenInterface $token): ?Organization
    {
        if ($token instanceof OrganizationAwareTokenInterface) {
            return $token->getOrganization();
        }

        return null;
    }
}
