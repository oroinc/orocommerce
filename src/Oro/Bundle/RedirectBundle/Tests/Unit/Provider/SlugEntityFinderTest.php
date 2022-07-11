<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Provider;

use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Provider\SlugEntityFinder;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use PHPUnit\Framework\MockObject\MockObject;

class SlugEntityFinderTest extends \PHPUnit\Framework\TestCase
{
    private SlugRepository|MockObject $repository;
    private ScopeManager|MockObject $scopeManager;
    private SlugEntityFinder $slugEntityFinder;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SlugRepository::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);

        $this->slugEntityFinder = new SlugEntityFinder($this->repository, $this->scopeManager);
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
