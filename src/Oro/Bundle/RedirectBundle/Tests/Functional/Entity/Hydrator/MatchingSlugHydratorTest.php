<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Entity\Hydrator;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\RedirectBundle\Entity\Hydrator\MatchingSlugHydrator;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugScopesData;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class MatchingSlugHydratorTest extends WebTestCase
{
    /**
     * @var SlugRepository
     */
    private $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->repository = self::getContainer()->get('doctrine')->getRepository(Slug::class);
        $this->loadFixtures([LoadSlugScopesData::class]);
    }

    public function testSlugWithMatchedScopeId()
    {
        $builder = $this->repository->createQueryBuilder('slug');
        $matchingSlugs = $builder
            ->leftJoin('slug.scopes', 'scopes', Join::WITH)
            ->addSelect('scopes.id as matchedScopeId')
            ->where(
                $builder->expr()->in(
                    'slug.url',
                    [LoadSlugsData::SLUG_URL_PAGE, LoadSlugsData::SLUG_URL_PAGE_2]
                )
            )
            ->getQuery()
            ->getResult(MatchingSlugHydrator::NAME);

        $this->assertCount(1, $matchingSlugs);

        $matchingSlug = reset($matchingSlugs);
        $expectedSlug = $this->repository->findOneBy(['url' => LoadSlugsData::SLUG_URL_PAGE]);

        $this->assertEquals($expectedSlug->getId(), $matchingSlug->getId());
    }

    public function testSlugWithEmptyScopes()
    {
        $customerNotForFirstPage = $this->getReference(LoadCustomers::CUSTOMER_LEVEL_1_1);
        $scopeNotForFirstPage = self::getContainer()->get('oro_scope.scope_manager')
            ->find('web_content', ['customer' => $customerNotForFirstPage]);

        $builder = $this->repository->createQueryBuilder('slug');
        $matchingSlugs = $builder
            ->leftJoin('slug.scopes', 'scopes', Join::WITH, $builder->expr()->eq('scopes', ':scope'))
            ->setParameter('scope', $scopeNotForFirstPage)
            ->addSelect('scopes.id as matchedScopeId')
            ->where(
                $builder->expr()->in(
                    'slug.url',
                    [LoadSlugsData::SLUG_URL_PAGE, LoadSlugsData::SLUG_URL_PAGE_2]
                )
            )
            ->getQuery()
            ->getResult(MatchingSlugHydrator::NAME);

        $this->assertCount(1, $matchingSlugs);

        $matchingSlug = reset($matchingSlugs);
        $expectedSlug = $this->repository->findOneBy(['url' => LoadSlugsData::SLUG_URL_PAGE_2]);

        $this->assertEquals($expectedSlug->getId(), $matchingSlug->getId());
    }

    public function testSlugWithoutMatches()
    {
        $customerNotForFirstPage = $this->getReference(LoadCustomers::CUSTOMER_LEVEL_1_1);
        $scopeNotForFirstPage = self::getContainer()->get('oro_scope.scope_manager')
            ->find('web_content', ['customer' => $customerNotForFirstPage]);

        $builder = $this->repository->createQueryBuilder('slug');
        $matchingSlug = $builder
            ->leftJoin('slug.scopes', 'scopes', Join::WITH, $builder->expr()->eq('scopes', ':scope'))
            ->setParameter('scope', $scopeNotForFirstPage)
            ->addSelect('scopes.id as matchedScopeId')
            ->where(
                $builder->expr()->in(
                    'slug.url',
                    [LoadSlugsData::SLUG_URL_PAGE]
                )
            )
            ->getQuery()
            ->getResult(MatchingSlugHydrator::NAME);

        $this->assertEmpty($matchingSlug);
    }
}
