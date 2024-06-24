<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\Repository\SearchTermRepository;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Tests\Functional\DataFixtures\LoadSearchTermData;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SearchTermRepositoryTest extends WebTestCase
{
    private ScopeManager $scopeManager;

    private SearchTermRepository $repository;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->loadFixtures([
            LoadSearchTermData::class,
        ]);

        $this->scopeManager = self::getContainer()->get('oro_scope.scope_manager');

        $this->repository = self::getContainer()
            ->get('doctrine')
            ->getRepository(SearchTerm::class);
    }

    /**
     * @dataProvider findSearchTermNotFoundDataProvider
     */
    public function testFindSearchTermByScopesWhenNotFound(string $searchString): void
    {
        $criteria = $this->scopeManager->getCriteria('website');
        $scopes = $this->repository->findMostSuitableUsedScopes($criteria);
        $searchTerm = $this->repository->findSearchTermByScopes($searchString, $scopes);

        self::assertTrue(null === $searchTerm);
    }

    /**
     * @dataProvider findSearchTermNotFoundDataProvider
     */
    public function testFindSearchTermWithPartialMatchByScopesWhenNotFound(string $searchString): void
    {
        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE);
        $scopes = $this->repository->findMostSuitableUsedScopes($criteria);
        $searchTerm = $this->repository->findSearchTermWithPartialMatchByScopes($searchString, $scopes);

        self::assertTrue(null === $searchTerm);
    }

    public function findSearchTermNotFoundDataProvider(): array
    {
        return [
            ['unknown'],
            ['lorem'],
            ['ipsum'],
            ['em ip'],
        ];
    }

    /**
     * @dataProvider findSearchTermByScopeDataProvider
     */
    public function testFindSearchTermByScope(string $searchString, string $expectedSearchTermReference): void
    {
        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE);
        $scopes = $this->repository->findMostSuitableUsedScopes($criteria);
        $searchTerm = $this->repository->findSearchTermByScopes($searchString, $scopes);

        self::assertInstanceOf(SearchTerm::class, $searchTerm);
        self::assertEquals(
            $this->getReference($expectedSearchTermReference)->getId(),
            $searchTerm->getId()
        );
    }

    /**
     * @dataProvider findSearchTermByScopesDataProvider
     */
    public function testFindSearchTermByScopes(string $searchString, string $expectedSearchTermReference): void
    {
        $criteria = $this->scopeManager->getCriteria(
            ScopeManager::BASE_SCOPE,
            ['website' => $this->getReference(LoadWebsiteData::WEBSITE1)]
        );
        $scopes = $this->repository->findMostSuitableUsedScopes($criteria);
        $searchTerm = $this->repository->findSearchTermByScopes($searchString, $scopes);

        self::assertInstanceOf(SearchTerm::class, $searchTerm);
        self::assertEquals(
            $this->getReference($expectedSearchTermReference)->getId(),
            $searchTerm->getId()
        );
    }

    public function findSearchTermByScopeDataProvider(): array
    {
        return [
            'foo is found by exact match' => [
                'searchString' => 'foo',
                'expectedSearchTermReference' => 'search-term:foo:url:foo.bar',
            ],
            'bar is found by exact match' => [
                'searchString' => 'bar',
                'expectedSearchTermReference' => 'search-term:bar:url:foo.bar',
            ],
            'lorem ipsum is found by exact match' => [
                'searchString' => 'lorem ipsum',
                'expectedSearchTermReference' => 'search-term:loremipsum:url:lorem.ipsum',
            ],
            'noscope is found by exact match' => [
                'searchString' => 'noscope',
                'expectedSearchTermReference' => 'search-term:noscope:url:foo.bar',
            ],
        ];
    }

    public function findSearchTermByScopesDataProvider(): array
    {
        return array_merge(
            $this->findSearchTermByScopeDataProvider(),
            [
                'baz is found by exact match' => [
                    'searchString' => 'baz',
                    'expectedSearchTermReference' => 'search-term:baz:url:foo.baz',
                ],
            ]
        );
    }

    /**
     * @dataProvider findSearchTermByScopeDataProvider
     */
    public function testFindSearchTermWithPartialMatchByScope(
        string $searchString,
        string $expectedSearchTermReference
    ): void {
        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE);
        $scopes = $this->repository->findMostSuitableUsedScopes($criteria);
        $searchTerm = $this->repository->findSearchTermByScopes($searchString, $scopes);

        self::assertInstanceOf(SearchTerm::class, $searchTerm);
        self::assertEquals(
            $this->getReference($expectedSearchTermReference)->getId(),
            $searchTerm->getId()
        );
    }

    /**
     * @dataProvider findSearchTermByScopesDataProvider
     */
    public function testFindSearchTermWithPartialMatchByScopes(
        string $searchString,
        string $expectedSearchTermReference
    ): void {
        $criteria = $this->scopeManager->getCriteria(
            ScopeManager::BASE_SCOPE,
            ['website' => $this->getReference(LoadWebsiteData::WEBSITE1)]
        );
        $scopes = $this->repository->findMostSuitableUsedScopes($criteria);
        $searchTerm = $this->repository->findSearchTermByScopes($searchString, $scopes);

        self::assertInstanceOf(SearchTerm::class, $searchTerm);
        self::assertEquals(
            $this->getReference($expectedSearchTermReference)->getId(),
            $searchTerm->getId()
        );
    }

    public function findSearchTermWithPartialMatchByScopeDataProvider(): array
    {
        return [
            'foobar is found by partial match (start of the string)' => [
                'searchString' => 'foob',
                'expectedSearchTermReference' => 'search-term:foobar:url:foo.bar.partial',
            ],
            'foobar is found by partial match (middle of the string)' => [
                'searchString' => 'oob',
                'expectedSearchTermReference' => 'search-term:foobar:url:foo.bar.partial',
            ],
            'foobar is found by partial match (end of the string)' => [
                'searchString' => 'ar',
                'expectedSearchTermReference' => 'search-term:foobar:url:foo.bar.partial',
            ],
            'noscope is found by partial match (start of the string)' => [
                'searchString' => 'no',
                'expectedSearchTermReference' => 'search-term:noscope:url:foo.bar',
            ],
            'noscope is found by partial match (middle of the string)' => [
                'searchString' => 'osco',
                'expectedSearchTermReference' => 'search-term:noscope:url:foo.bar',
            ],
            'noscope is found by partial match (end of the string)' => [
                'searchString' => 'scope',
                'expectedSearchTermReference' => 'search-term:noscope:url:foo.bar',
            ],
        ];
    }

    public function findSearchTermWithPartialMatchByScopesDataProvider(): array
    {
        return array_merge(
            $this->findSearchTermWithPartialMatchByScopeDataProvider(),
            [
                'bazfoo is found by partial match (start of the string)' => [
                    'searchString' => 'bazf',
                    'expectedSearchTermReference' => 'search-term:bazfoo:url:baz.foo.partial',
                ],
                'bazfoo is found by partial match (middle of the string)' => [
                    'searchString' => 'azf',
                    'expectedSearchTermReference' => 'search-term:bazfoo:url:baz.foo.partial',
                ],
                'bazfoo is found by partial match (end of the string)' => [
                    'searchString' => 'zfoo',
                    'expectedSearchTermReference' => 'search-term:bazfoo:url:baz.foo.partial',
                ],
            ]
        );
    }
}
