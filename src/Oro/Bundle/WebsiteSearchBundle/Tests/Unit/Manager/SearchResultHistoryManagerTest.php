<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WebsiteSearchBundle\Manager\SearchResultHistoryManager;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\Repository\SearchResultHistoryRepository;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\Repository\SearchTermReportRepository;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SearchResultHistoryManagerTest extends TestCase
{
    use EntityTrait;

    /**
     * @var SearchResultHistoryRepository|MockObject
     */
    private $historyRepository;

    /**
     * @var SearchTermReportRepository|MockObject
     */
    private $reportRepository;

    /**
     * @var MockObject|TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var WebsiteManager|MockObject
     */
    private $websiteManager;

    /**
     * @var LocalizationHelper|MockObject
     */
    private $localizationHelper;

    /**
     * @var ConfigManager|MockObject
     */
    private $configManager;

    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    private SearchResultHistoryManager $manager;

    protected function setUp(): void
    {
        $this->historyRepository = $this->createMock(SearchResultHistoryRepository::class);
        $this->reportRepository = $this->createMock(SearchTermReportRepository::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->manager = new SearchResultHistoryManager(
            $this->historyRepository,
            $this->reportRepository,
            $this->tokenStorage,
            $this->websiteManager,
            $this->localizationHelper,
            $this->configManager
        );
        $this->manager->setLogger($this->logger);
    }

    public function testSaveSearchResultForLoggedInCustomerUser(): void
    {
        $searchTerm = 'TEST   search   Term';
        $searchType = 'test search type';
        $resultsCount = 10;
        $searchSessionId = 'test search session id';


        $organization = $this->getEntity(Organization::class, ['id' => 1]);
        $businessUnit = $this->getEntity(BusinessUnit::class, ['id' => 2]);
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 3]);
        $website = $this->getEntity(Website::class, ['id' => 4]);
        $website->setOwner($businessUnit);
        $localization = $this->getEntity(Localization::class, ['id' => 5]);
        $customer = $this->getEntity(Customer::class, ['id' => 6]);
        $customerUser->setCustomer($customer);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $token = $this->createMock(OrganizationAwareTokenInterface::class);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->historyRepository->expects($this->once())
            ->method('upsertSearchHistoryRecord')
            ->with(
                $searchTerm,
                $searchType,
                $resultsCount,
                md5('test search term'),
                $businessUnit->getId(),
                $website->getId(),
                $searchSessionId,
                $localization->getId(),
                $customer->getId(),
                $customerUser->getId(),
                null,
                $organization->getId()
            );

        $this->manager->saveSearchResult($searchTerm, $searchType, $resultsCount, $searchSessionId);
    }

    public function testSaveSearchResultForAnonymous(): void
    {
        $searchTerm = 'TEST   search   Term';
        $searchType = 'test search type';
        $resultsCount = 10;
        $searchSessionId = 'test search session id';

        $customerVisitor = $this->getEntity(CustomerVisitor::class, ['id' => 42]);
        $organization = $this->getEntity(Organization::class, ['id' => 1]);
        $businessUnit = $this->getEntity(BusinessUnit::class, ['id' => 2]);
        $website = $this->getEntity(Website::class, ['id' => 4]);
        $website->setOwner($businessUnit);
        $localization = $this->getEntity(Localization::class, ['id' => 5]);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);
        $token->expects($this->once())
            ->method('getVisitor')
            ->willReturn($customerVisitor);

        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->historyRepository->expects($this->once())
            ->method('upsertSearchHistoryRecord')
            ->with(
                $searchTerm,
                $searchType,
                $resultsCount,
                md5('test search term'),
                $businessUnit->getId(),
                $website->getId(),
                $searchSessionId,
                $localization->getId(),
                null,
                null,
                $customerVisitor->getId(),
                $organization->getId()
            );

        $this->manager->saveSearchResult($searchTerm, $searchType, $resultsCount, $searchSessionId);
    }

    public function testSaveSearchResultNoOwner(): void
    {
        $searchTerm = 'TEST   search   Term';
        $searchType = 'test search type';
        $resultsCount = 10;
        $searchSessionId = 'test search session id';

        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->historyRepository->expects($this->never())
            ->method('upsertSearchHistoryRecord');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Unable to get owner for SearchResultHistory entity');

        $this->manager->saveSearchResult($searchTerm, $searchType, $resultsCount, $searchSessionId);
    }

    public function testSaveSearchResultException(): void
    {
        $searchTerm = 'TEST   search   Term';
        $searchType = 'test search type';
        $resultsCount = 10;
        $searchSessionId = 'test search session id';

        $customerVisitor = $this->getEntity(CustomerVisitor::class, ['id' => 42]);
        $organization = $this->getEntity(Organization::class, ['id' => 1]);
        $businessUnit = $this->getEntity(BusinessUnit::class, ['id' => 2]);
        $website = $this->getEntity(Website::class, ['id' => 4]);
        $website->setOwner($businessUnit);
        $localization = $this->getEntity(Localization::class, ['id' => 5]);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);
        $token->expects($this->once())
            ->method('getVisitor')
            ->willReturn($customerVisitor);

        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $exception = new \RuntimeException();
        $this->historyRepository->expects($this->once())
            ->method('upsertSearchHistoryRecord')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Unable to save search term', ['exception' => $exception]);

        $this->manager->saveSearchResult($searchTerm, $searchType, $resultsCount, $searchSessionId);
    }

    public function testRemoveOutdatedHistoryRecords()
    {
        $this->historyRepository->expects($this->once())
            ->method('removeOldRecords');

        $this->manager->removeOutdatedHistoryRecords();
    }

    public function testActualizeHistoryReport()
    {
        $organization = $this->getEntity(Organization::class, ['id' => 10]);

        $this->historyRepository->expects($this->once())
            ->method('getOrganizationsByHistory')
            ->willReturn([$organization]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_locale.timezone', 'UTC', false, $organization)
            ->willReturn('Europe/Kyiv');

        $this->reportRepository->expects($this->once())
            ->method('actualizeReport')
            ->with($organization, new \DateTimeZone('Europe/Kyiv'));

        $this->manager->actualizeHistoryReport();
    }
}
