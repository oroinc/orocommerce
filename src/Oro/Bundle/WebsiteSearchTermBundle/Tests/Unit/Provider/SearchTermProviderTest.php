<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\Repository\SearchTermRepository;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Provider\SearchTermProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SearchTermProviderTest extends TestCase
{
    private ScopeManager|MockObject $scopeManager;

    private SearchTermRepository|MockObject $searchTermRepo;

    protected function setUp(): void
    {
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $doctrine = $this->createMock(ManagerRegistry::class);

        $this->provider = new SearchTermProvider($this->scopeManager, $doctrine);

        $this->searchTermRepo = $this->createMock(SearchTermRepository::class);
        $doctrine
            ->method('getRepository')
            ->with(SearchTerm::class)
            ->willReturn($this->searchTermRepo);
    }

    public function testGetMostSuitableSearchTermWhenEmptySearchPhrase(): void
    {
        $criteria = new ScopeCriteria(['website' => new Website()], $this->createMock(ClassMetadataFactory::class));
        $this->scopeManager
            ->expects(self::once())
            ->method('getCriteria')
            ->with('website_search_term')
            ->willReturn($criteria);

        $scope = new Scope();
        $this->searchTermRepo
            ->expects(self::once())
            ->method('findMostSuitableUsedScopes')
            ->with($criteria)
            ->willReturn([$scope]);

        $searchPhrase = '';
        $this->searchTermRepo
            ->expects(self::once())
            ->method('findSearchTermByScopes')
            ->with($searchPhrase, [$scope])
            ->willReturn(null);


        $this->searchTermRepo
            ->expects(self::never())
            ->method('findSearchTermWithPartialMatchByScopes');

        self::assertNull($this->provider->getMostSuitableSearchTerm($searchPhrase));
    }

    public function testGetMostSuitableSearchTermWhenNoSearchTermFound(): void
    {
        $criteria = new ScopeCriteria(['website' => new Website()], $this->createMock(ClassMetadataFactory::class));
        $this->scopeManager
            ->expects(self::once())
            ->method('getCriteria')
            ->with('website_search_term')
            ->willReturn($criteria);

        $scope = new Scope();
        $this->searchTermRepo
            ->expects(self::once())
            ->method('findMostSuitableUsedScopes')
            ->with($criteria)
            ->willReturn([$scope]);

        $searchPhrase = 'sample phrase';
        $this->searchTermRepo
            ->expects(self::once())
            ->method('findSearchTermByScopes')
            ->with($searchPhrase, [$scope])
            ->willReturn(null);

        $this->searchTermRepo
            ->expects(self::once())
            ->method('findSearchTermWithPartialMatchByScopes')
            ->with($searchPhrase, [$scope])
            ->willReturn(null);

        self::assertNull($this->provider->getMostSuitableSearchTerm($searchPhrase));
    }

    public function testGetMostSuitableSearchTermWhenExactSearchTermFound(): void
    {
        $criteria = new ScopeCriteria(['website' => new Website()], $this->createMock(ClassMetadataFactory::class));
        $this->scopeManager
            ->expects(self::once())
            ->method('getCriteria')
            ->with('website_search_term')
            ->willReturn($criteria);

        $scope = new Scope();
        $this->searchTermRepo
            ->expects(self::once())
            ->method('findMostSuitableUsedScopes')
            ->with($criteria)
            ->willReturn([$scope]);

        $searchPhrase = 'sample phrase';
        $searchTerm = new SearchTerm();
        $this->searchTermRepo
            ->expects(self::once())
            ->method('findSearchTermByScopes')
            ->with($searchPhrase, [$scope])
            ->willReturn($searchTerm);

        $this->searchTermRepo
            ->expects(self::never())
            ->method('findSearchTermWithPartialMatchByScopes');

        self::assertSame($searchTerm, $this->provider->getMostSuitableSearchTerm($searchPhrase));
    }

    public function testGetMostSuitableSearchTermWhenSearchTermWithPartialMatchFound(): void
    {
        $criteria = new ScopeCriteria(['website' => new Website()], $this->createMock(ClassMetadataFactory::class));
        $this->scopeManager
            ->expects(self::once())
            ->method('getCriteria')
            ->with('website_search_term')
            ->willReturn($criteria);

        $scope = new Scope();
        $this->searchTermRepo
            ->expects(self::once())
            ->method('findMostSuitableUsedScopes')
            ->with($criteria)
            ->willReturn([$scope]);

        $searchPhrase = 'sample phrase';
        $searchTerm = new SearchTerm();
        $this->searchTermRepo
            ->expects(self::once())
            ->method('findSearchTermByScopes')
            ->with($searchPhrase, [$scope])
            ->willReturn(null);

        $this->searchTermRepo
            ->expects(self::once())
            ->method('findSearchTermWithPartialMatchByScopes')
            ->with($searchPhrase, [$scope])
            ->willReturn($searchTerm);

        self::assertSame($searchTerm, $this->provider->getMostSuitableSearchTerm($searchPhrase));
    }

    public function testGetMostSuitableSearchTermWhenSearchTermLengthIsNotEnoughForPartialMatch(): void
    {
        $criteria = new ScopeCriteria(['website' => new Website()], $this->createMock(ClassMetadataFactory::class));
        $this->scopeManager
            ->expects(self::once())
            ->method('getCriteria')
            ->with('website_search_term')
            ->willReturn($criteria);

        $scope = new Scope();
        $this->searchTermRepo
            ->expects(self::once())
            ->method('findMostSuitableUsedScopes')
            ->with($criteria)
            ->willReturn([$scope]);

        $searchPhrase = 'sam';
        $this->searchTermRepo
            ->expects(self::once())
            ->method('findSearchTermByScopes')
            ->with($searchPhrase, [$scope])
            ->willReturn(null);

        $this->searchTermRepo
            ->expects(self::never())
            ->method('findSearchTermWithPartialMatchByScopes');

        self::assertNull($this->provider->getMostSuitableSearchTerm($searchPhrase));
    }
}
