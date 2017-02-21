<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
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
 */
class SlugRepositoryTest extends WebTestCase
{
    /**
     * @var SlugRepository
     */
    protected $repository;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->client->useHashNavigation(true);
        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository(Slug::class);
        $this->scopeManager = $this->getContainer()->get('oro_scope.scope_manager');

        $organization = $this->getContainer()->get('doctrine')
            ->getRepository('OroOrganizationBundle:Organization')
            ->getFirst();
        $token = new UsernamePasswordOrganizationToken(
            LoadCustomerUserData::AUTH_USER,
            'admin',
            'key',
            $organization
        );
        $this->client->getContainer()->get('security.token_storage')->setToken($token);

        $this->loadFixtures(
            [
                LoadSlugScopesData::class
            ]
        );
    }

    public function testGetSlugByUrlAndScopeCriteriaAnonymous()
    {
        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_URL_ANONYMOUS, $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);
        $this->assertSame($expected, $slug);
    }

    public function testGetSlugByUrlAndScopeCriteriaUser()
    {
        /** @var CustomerUser $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);

        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE, ['customer' => $customer]);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_URL_USER, $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_USER);
        $this->assertSame($expected, $slug);
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
        $this->assertSame($expected, $slug);
    }

    public function testGetSlugByUrlAndScopeCriteriaSlugWithoutScopesNotEmptyCriteria()
    {
        /** @var CustomerUser $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);

        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE, ['customer' => $customer]);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_URL_ANONYMOUS, $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);
        $this->assertSame($expected, $slug);
    }

    public function testGetSlugByUrlAndScopeCriteriaSlugWithScopesMatched()
    {
        /** @var CustomerUser $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);

        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE, ['customer' => $customer]);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_TEST_DUPLICATE_URL, $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_TEST_DUPLICATE_URL);
        $this->assertSame($expected, $slug);
    }

    public function testGetSlugByUrlAndScopeCriteriaSlugWithoutScopesMatched()
    {
        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_TEST_DUPLICATE_URL, $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_TEST_DUPLICATE_REFERENCE);
        $this->assertSame($expected, $slug);
    }

    public function testFindOneDirectUrlBySlug()
    {
        $actual = $this->repository->findOneDirectUrlBySlug('/slug/first');

        $this->assertNotEmpty($actual);
        $this->assertEquals($this->getReference('reference:/slug/first')->getId(), $actual->getId());
    }

    public function testFindOneDirectUrlBySlugScopedSlug()
    {
        $actual = $this->repository->findOneDirectUrlBySlug('/slug/page');

        $this->assertEmpty($actual);
    }

    public function testFindOneDirectUrlBySlugWithRestriction()
    {
        $restrictedEntity = $this->createMock(SluggableInterface::class);
        $restrictedEntity->expects($this->any())
            ->method('getSlugs')
            ->willReturn([$this->getReference('reference:/slug/first')]);

        $actual = $this->repository->findOneDirectUrlBySlug('/slug/first', $restrictedEntity);
        $this->assertEmpty($actual);
    }

    /**
     * @dataProvider findAllByPatternWithoutScopeDataProvider
     * @param string $pattern
     * @param array $expected
     */
    public function testFindAllDirectUrlsByPattern($pattern, array $expected)
    {
        $actual = $this->repository->findAllDirectUrlsByPattern($pattern);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function findAllByPatternWithoutScopeDataProvider()
    {
        return [
            [
                'pattern' => '/slug/f%',
                'expected' => [
                    '/slug/first'
                ]
            ],
            [
                'pattern' => '/slug%',
                'expected' => [
                    '/slug/anonymous',
                    '/slug/first',
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
        $this->assertSame($expected, $slug);
    }

    public function testGetSlugBySlugPrototypeAndScopeCriteriaUser()
    {
        /** @var CustomerUser $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);

        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE, ['customer' => $customer]);
        $slug = $this->repository->getSlugBySlugPrototypeAndScopeCriteria('page', $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_PAGE);
        $this->assertSame($expected, $slug);
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
                'slugPrototype' => $slug1->getSlugPrototype()
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

    public function testGetUsedRoutes()
    {
        $expected = [
            'oro_cms_frontend_page_view',
            'oro_customer_frontend_customer_user_index',
            '__test__'
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
}
