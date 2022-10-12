<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Generator;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;
use Oro\Bundle\RedirectBundle\Generator\UniqueSlugResolver;
use Oro\Component\Testing\ReflectionUtil;

class UniqueSlugResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var SlugRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var UniqueSlugResolver */
    private $uniqueSlugResolver;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SlugRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($this->repository);

        $this->uniqueSlugResolver = new UniqueSlugResolver($doctrine);
    }

    private function getSlug(int $id): Slug
    {
        $slug = new Slug();
        ReflectionUtil::setId($slug, $id);

        return $slug;
    }

    public function testResolveNewSlug()
    {
        $slug = '/test';
        $slugUrl = new SlugUrl($slug);

        $entity = $this->createMock(SluggableInterface::class);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository->expects($this->once())
            ->method('getOneDirectUrlBySlugQueryBuilder')
            ->with($slug, $entity)
            ->willReturn($queryBuilder);

        $this->assertEquals($slug, $this->uniqueSlugResolver->resolve($slugUrl, $entity));
    }

    public function testResolveExistingSlug()
    {
        $slug = '/test';
        $existingSlug = '/test-1';
        $expectedSlug = '/test-2';

        $slugUrl = new SlugUrl($slug);

        $entity = $this->createMock(SluggableInterface::class);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(new Slug());
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository->expects($this->once())
            ->method('getOneDirectUrlBySlugQueryBuilder')
            ->with($slug, $entity)
            ->willReturn($queryBuilder);

        $this->repository->expects($this->once())
            ->method('findAllDirectUrlsByPattern')
            ->with('/test-%', $entity)
            ->willReturn([$existingSlug]);

        $this->assertEquals($expectedSlug, $this->uniqueSlugResolver->resolve($slugUrl, $entity));
    }

    public function testResolveExistingSlugWithinBatch()
    {
        $slug = '/test';
        /** @var Localization $frLocalization */
        $frLocalization = $this->createMock(Localization::class);

        $slugUrl = new SlugUrl($slug);
        $slugUrlFr = new SlugUrl($slug, $frLocalization);

        $entity1 = $this->createMock(SluggableInterface::class);
        $entity1->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $entity2 = $this->createMock(SluggableInterface::class);
        $entity2->expects($this->any())
            ->method('getId')
            ->willReturn(2);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->any())
            ->method('getOneOrNullResult')
            ->willReturn(null);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->exactly(2))
            ->method('getQuery')
            ->willReturn($query);

        $this->repository->expects($this->any())
            ->method('getOneDirectUrlBySlugQueryBuilder')
            ->willReturn($queryBuilder);

        $this->repository->expects($this->any())
            ->method('findAllDirectUrlsByPattern')
            ->willReturn([]);

        $this->assertEquals($slug, $this->uniqueSlugResolver->resolve($slugUrl, $entity1));
        $this->assertEquals($slug, $this->uniqueSlugResolver->resolve($slugUrlFr, $entity1));
        $this->assertEquals('/test-1', $this->uniqueSlugResolver->resolve($slugUrl, $entity2));
        $this->assertEquals('/test-1', $this->uniqueSlugResolver->resolve($slugUrlFr, $entity2));
    }

    public function testResolveExistingIncrementedSlug()
    {
        $slug = '/test-1';
        $existingSlug = '/test-1';
        $expectedSlug = '/test-2';

        $slugUrl = new SlugUrl($slug);

        $entity = $this->createMock(SluggableInterface::class);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->exactly(2))
            ->method('getOneOrNullResult')
            ->willReturnOnConsecutiveCalls([$this->getSlug(123)], [$this->getSlug(42)]);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->exactly(2))
            ->method('getQuery')
            ->willReturn($query);

        $this->repository->expects($this->exactly(2))
            ->method('getOneDirectUrlBySlugQueryBuilder')
            ->withConsecutive(
                [$slug, $entity],
                ['/test', $entity]
            )
            ->willReturn($queryBuilder);

        $this->repository->expects($this->once())
            ->method('findAllDirectUrlsByPattern')
            ->with('/test-%', $entity)
            ->willReturn([$existingSlug]);

        $this->assertEquals($expectedSlug, $this->uniqueSlugResolver->resolve($slugUrl, $entity));
    }
}
