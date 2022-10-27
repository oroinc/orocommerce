<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Provider\SlugEntityFinder;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class SlugEntityFinderTest extends \PHPUnit\Framework\TestCase
{
    /** @var SlugRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeManager;

    /** @var SlugEntityFinder */
    private $slugEntityFinder;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SlugRepository::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($this->repository);

        $this->slugEntityFinder = new SlugEntityFinder($doctrine, $this->scopeManager);
    }

    private function expectsGetScopeCriteria(): ScopeCriteria
    {
        $scopeCriteria = $this->createMock(ScopeCriteria::class);
        $this->scopeManager->expects(self::once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($scopeCriteria);

        return $scopeCriteria;
    }

    public function getSlugDataProvider(): array
    {
        return [
            [$this->createMock(Slug::class)],
            [null]
        ];
    }

    /**
     * @dataProvider getSlugDataProvider
     */
    public function testFindSlugEntityByUrl(?Slug $result): void
    {
        $url = '/test';
        $scopeCriteria = $this->expectsGetScopeCriteria();
        $this->repository->expects(self::once())
            ->method('getSlugByUrlAndScopeCriteria')
            ->with($url, self::identicalTo($scopeCriteria))
            ->willReturn($result);

        self::assertSame($result, $this->slugEntityFinder->findSlugEntityByUrl($url));
    }

    public function testFindSlugEntityByUrlShouldCacheScopeCriteria(): void
    {
        $url = '/test';
        $slug = $this->createMock(Slug::class);
        $scopeCriteria = $this->expectsGetScopeCriteria();
        $this->repository->expects(self::exactly(2))
            ->method('getSlugByUrlAndScopeCriteria')
            ->with($url, self::identicalTo($scopeCriteria))
            ->willReturn($slug);

        self::assertSame($slug, $this->slugEntityFinder->findSlugEntityByUrl($url));
        // test that the scope criteria is cached
        self::assertSame($slug, $this->slugEntityFinder->findSlugEntityByUrl($url));
    }

    /**
     * @dataProvider getSlugDataProvider
     */
    public function testFindSlugEntityBySlugPrototype(?Slug $result): void
    {
        $slugPrototype = '/test';
        $scopeCriteria = $this->expectsGetScopeCriteria();
        $this->repository->expects(self::once())
            ->method('getSlugBySlugPrototypeAndScopeCriteria')
            ->with($slugPrototype, self::identicalTo($scopeCriteria))
            ->willReturn($result);

        self::assertSame($result, $this->slugEntityFinder->findSlugEntityBySlugPrototype($slugPrototype));
    }

    public function testFindSlugEntityBySlugPrototypeShouldCacheScopeCriteria(): void
    {
        $slugPrototype = '/test';
        $slug = $this->createMock(Slug::class);
        $scopeCriteria = $this->expectsGetScopeCriteria();
        $this->repository->expects(self::exactly(2))
            ->method('getSlugBySlugPrototypeAndScopeCriteria')
            ->with($slugPrototype, self::identicalTo($scopeCriteria))
            ->willReturn($slug);

        self::assertSame($slug, $this->slugEntityFinder->findSlugEntityBySlugPrototype($slugPrototype));
        // test that the scope criteria is cached
        self::assertSame($slug, $this->slugEntityFinder->findSlugEntityBySlugPrototype($slugPrototype));
    }
}
