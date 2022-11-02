<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugScopesData;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class SlugRepositoryTest extends WebTestCase
{
    private SlugRepository $repository;
    private ScopeManager $scopeManager;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(
                LoadCustomerUserData::AUTH_USER,
                LoadCustomerUserData::AUTH_PW
            )
        );
        $this->loadFixtures([LoadSlugScopesData::class]);

        $this->client->useHashNavigation(true);
        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository(Slug::class);
        $this->scopeManager = $this->getContainer()->get('oro_scope.scope_manager');

        $organization = $this->getContainer()->get('doctrine')
            ->getRepository(Organization::class)
            ->getFirst();
        $token = new UsernamePasswordOrganizationToken(
            LoadCustomerUserData::AUTH_USER,
            'admin',
            'key',
            $organization
        );
        $this->client->getContainer()->get('security.token_storage')->setToken($token);
    }

    public function testGetSlugByUrlAndScopeCriteriaAnonymous()
    {
        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(
            LoadSlugsData::SLUG_URL_ANONYMOUS,
            $criteria
        );
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);

        $this->assertNotEmpty($slug);
        $this->assertSame($expected->getId(), $slug->getId());
    }

    public function testGetSlugByUrlAndScopeCriteriaUser()
    {
        /** @var CustomerUser $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);

        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE, ['customer' => $customer]);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_URL_USER, $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_USER);

        $this->assertNotEmpty($slug);
        $this->assertSame($expected->getId(), $slug->getId());
    }

    public function testGetSlugByUrlAndScopeCriteriaWhenSlugHasScopesThatNotMatches()
    {
        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_URL_PAGE, $criteria);

        $this->assertNull($slug);
    }

    public function testGetSlugByUrlAndScopeCriteriaSlugWithoutScopes()
    {
        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_URL_ANONYMOUS, $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);

        $this->assertNotEmpty($slug);
        $this->assertSame($expected->getId(), $slug->getId());
    }

    public function testGetSlugByUrlAndScopeCriteriaSlugWithoutScopesNotEmptyCriteria()
    {
        /** @var CustomerUser $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);

        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE, ['customer' => $customer]);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_URL_ANONYMOUS, $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);

        $this->assertNotEmpty($slug);
        $this->assertSame($expected->getId(), $slug->getId());
    }

    public function testGetSlugByUrlAndScopeCriteriaSlugWithScopesMatched()
    {
        /** @var CustomerUser $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);

        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE, ['customer' => $customer]);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_TEST_URL, $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_TEST_URL);

        $this->assertNotEmpty($slug);
        $this->assertSame($expected->getId(), $slug->getId());
    }

    public function testGetSlugByUrlAndScopeCriteriaSlugWithoutScopesMatched()
    {
        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_TEST_URL, $criteria);
        $this->assertEmpty($slug);
    }

    public function testFindOneDirectUrlBySlug()
    {
        /** @var CustomerUser $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);
        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE, ['customer' => $customer]);

        $qb = $this->repository->getOneDirectUrlBySlugQueryBuilder(
            LoadSlugsData::SLUG_TEST_URL,
            null,
            $criteria
        );
        $actual = $qb->getQuery()->getOneOrNullResult();

        $this->assertNotEmpty($actual);
        $this->assertEquals($this->getReference(LoadSlugsData::SLUG_TEST_URL)->getId(), $actual->getId());
    }

    public function testFindOneDirectUrlBySlugScopedSlug()
    {
        $qb = $this->repository->getOneDirectUrlBySlugQueryBuilder('/slug/page');
        $actual = $qb->getQuery()->getOneOrNullResult();

        $this->assertEmpty($actual);
    }

    public function testFindOneDirectUrlBySlugWithRestriction()
    {
        $restrictedEntity = $this->createMock(SluggableInterface::class);
        $restrictedEntity->expects($this->any())
            ->method('getSlugs')
            ->willReturn([$this->getReference('reference:/slug/first')]);

        $qb = $this->repository->getOneDirectUrlBySlugQueryBuilder('/slug/first', $restrictedEntity);
        $actual = $qb->getQuery()->getOneOrNullResult();
        $this->assertEmpty($actual);
    }

    /**
     * @dataProvider findAllByPatternWithoutScopeDataProvider
     */
    public function testFindAllDirectUrlsByPattern($pattern, ?array $criteria, array $expected)
    {
        if ($criteria) {
            array_walk($criteria, fn (&$item) => $item = $this->getReference($item));
            $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE, $criteria);
        }

        $actual = $this->repository->findAllDirectUrlsByPattern($pattern, null, $criteria);
        $this->assertEquals($expected, $actual);
    }

    public function findAllByPatternWithoutScopeDataProvider(): array
    {
        return [
            [
                'pattern' => '/slug/f%',
                'criteriaContext' => ['customer' => LoadCustomers::DEFAULT_ACCOUNT_NAME],
                'expected' => [
                    '/slug/first'
                ]
            ],
            [
                'pattern' => '/slug%',
                'criteriaContext' => null,
                'expected' => [
                    '/slug/anonymous',
                    '/slug/page2',
                    '/slug/en/page',
                    '/slug/es/page'
                ]
            ]
        ];
    }

    public function testFindAllDirectUrlsByPatternWithRestriction()
    {
        $restrictedEntity = $this->createMock(SluggableInterface::class);
        $restrictedEntity->expects($this->any())
            ->method('getSlugs')
            ->willReturn([$this->getReference('reference:/slug/first')]);

        $actual = $this->repository->findAllDirectUrlsByPattern('/slug/f%', $restrictedEntity);
        $this->assertEmpty($actual);
    }

    public function testFindAllDirectUrlsByPatternScopedSlug()
    {
        $actual = $this->repository->findAllDirectUrlsByPattern('/slug/page');

        $this->assertEmpty($actual);
    }

    public function testGetUsedScopes()
    {
        $this->assertSame(
            [$this->getReference(LoadSlugScopesData::SCOPE_KEY)],
            $this->repository->getUsedScopes()
        );
    }

    public function testGetSlugBySlugPrototypeAndScopeCriteriaAnonymous()
    {
        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE);
        $slug = $this->repository->getSlugBySlugPrototypeAndScopeCriteria('anonymous', $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);

        $this->assertNotEmpty($slug);
        $this->assertSame($expected->getId(), $slug->getId());
    }

    public function testGetSlugBySlugPrototypeAndScopeCriteriaUser()
    {
        /** @var CustomerUser $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);

        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE, ['customer' => $customer]);
        $slug = $this->repository->getSlugBySlugPrototypeAndScopeCriteria('page2', $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_PAGE_2);

        $this->assertNotEmpty($slug);
        $this->assertSame($expected->getId(), $slug->getId());
    }

    public function testGetSlugDataForDirectUrls()
    {
        /** @var Slug $slug1 */
        $slug1 = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);
        /** @var Slug $slug2 */
        $slug2 = $this->getReference(LoadSlugsData::SLUG_URL_USER);

        $actual = [];
        foreach ($this->repository->getSlugDataForDirectUrls([$slug1->getId(), $slug2->getId()]) as $data) {
            $actual[] = $data;
        }

        $expected = [
            [
                'routeParameters' => $slug1->getRouteParameters(),
                'url' => $slug1->getUrl(),
                'slugPrototype' => $slug1->getSlugPrototype(),
                'localization_id' => $slug1->getLocalization()?->getId()
            ]
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testIsScopeAttachedToSlug()
    {
        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_URL_USER);

        $this->assertTrue($this->repository->isScopeAttachedToSlug($slug->getScopes()->first()));
    }

    public function testFindMostSuitableUsedScope()
    {
        $scope = $this->getReference(LoadSlugScopesData::SCOPE_KEY);
        /** @var Customer $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);
        $criteria = $this->scopeManager->getCriteria('web_content', ['customer' => $customer]);
        $this->assertSame($scope, $this->repository->findMostSuitableUsedScope($criteria));
    }

    public function testFindMostSuitableUsedScopeEmptyResult()
    {
        /** @var Customer $secondCustomer */
        $secondCustomer = $this->getReference(LoadCustomers::CUSTOMER_LEVEL_1_1);
        $nonUsedCriteria = $this->scopeManager->getCriteria('web_content', ['customer' => $secondCustomer]);
        $this->assertNull($this->repository->findMostSuitableUsedScope($nonUsedCriteria));
    }

    public function testGetUsedRoutes()
    {
        $expected = [
            'oro_cms_frontend_page_view',
            'oro_customer_frontend_customer_user_index',
            '__test__',
            'oro_product_frontend_product_view'
        ];
        sort($expected);
        $actual = $this->repository->getUsedRoutes();
        sort($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetSlugsCountByRoute()
    {
        $this->assertEquals(1, $this->repository->getSlugsCountByRoute('__test__'));
    }

    public function testGetSlugIdsByRoute()
    {
        $expected = [
            $this->getReference(LoadSlugsData::SLUG_TEST_ONLY)->getId(),
        ];
        $actual = $this->repository->getSlugIdsByRoute('__test__', 0, 100);
        $this->assertEquals($expected, $actual);
    }

    public function testGetRawRedirectSlug()
    {
        /** @var Slug $expected */
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_PAGE_2);

        $slug = $this->repository->getRawSlug(
            'oro_cms_frontend_page_view',
            [
                'id' => $this->getReference(LoadPageData::PAGE_2)->getId()
            ],
            null
        );

        $this->assertNotEmpty($slug);
        $this->assertEquals(
            [
                'url' => $expected->getUrl(),
                'slug_prototype' => $expected->getSlugPrototype()
            ],
            $slug
        );
    }

    public function testGetRawRedirectSlugLocalized()
    {
        /** @var Slug $expected */
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_LOCALIZATION_2);

        $slug = $this->repository->getRawSlug(
            'oro_product_frontend_product_view',
            [
                'id' => $this->getReference(LoadPageData::PAGE_1)->getId()
            ],
            $this->getReference('es')->getId()
        );

        $this->assertNotEmpty($slug);
        $this->assertEquals(
            [
                'url' => $expected->getUrl(),
                'slug_prototype' => $expected->getSlugPrototype()
            ],
            $slug
        );

        $slug = $this->repository->getRawSlug(
            'oro_product_frontend_product_view',
            [
                'id' => $this->getReference(LoadPageData::PAGE_1)->getId()
            ],
            null
        );

        $this->assertNotEmpty($slug);
        $this->assertNotEquals(
            [
                'url' => $expected->getUrl(),
                'slug_prototype' => $expected->getSlugPrototype()
            ],
            $slug
        );
    }

    public function testGetRawRedirectSlugForDuplicateSlug()
    {
        /** @var Slug $expected */
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);

        $slug = $this->repository->getRawSlug(
            'oro_cms_frontend_page_view',
            [
                'id' => $this->getReference(LoadPageData::PAGE_1)->getId()
            ],
            null
        );

        $this->assertNotEmpty($slug);
        $this->assertEquals(
            [
                'url' => $expected->getUrl(),
                'slug_prototype' => $expected->getSlugPrototype()
            ],
            $slug
        );
    }

    public function testGetRawRedirectSlugWhenNoSlug()
    {
        $slug = $this->repository->getRawSlug(
            'oro_customer_frontend_customer_user_create',
            [],
            null
        );
        $this->assertNull($slug);
    }

    /**
     * @dataProvider isSlugForRouteExistsDataProvider
     */
    public function testIsSlugForRouteExists(string $routeName, bool $expectedResult): void
    {
        $actualResult = $this->repository->isSlugForRouteExists($routeName);

        self::assertEquals($expectedResult, $actualResult);
    }

    public function isSlugForRouteExistsDataProvider(): array
    {
        return [
            ['oro_product_frontend_product_view', true],
            ['oro_product_frontend_product_index', false],
            ['oro_cms_frontend_page_view', true],
            ['oro_rfp_frontend_request_view', false],
        ];
    }
}
