<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentNodeUtils\Loader;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Factory\ResolvedContentVariantFactory;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Loader\ResolvedContentVariantsLoader;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentVariantRepository;

class ResolvedContentVariantsLoaderTest extends \PHPUnit\Framework\TestCase
{
    private ResolvedContentVariantFactory|\PHPUnit\Framework\MockObject\MockObject $resolvedContentVariantFactory;

    private ContentVariantRepository|\PHPUnit\Framework\MockObject\MockObject $repository;

    private ResolvedContentVariantsLoader $loader;

    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->resolvedContentVariantFactory = $this->createMock(ResolvedContentVariantFactory::class);

        $this->loader = new ResolvedContentVariantsLoader(
            $managerRegistry,
            $this->resolvedContentVariantFactory
        );

        $this->repository = $this->createMock(ContentVariantRepository::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->with(ContentVariant::class)
            ->willReturn($this->repository);

        $this->resolvedContentVariantFactory
            ->expects(self::any())
            ->method('createFromArray')
            ->willReturnCallback(fn (array $data) => $this->createResolvedVariant($data));
    }

    public function testWhenNoContentVariantIds(): void
    {
        $this->resolvedContentVariantFactory
            ->expects(self::never())
            ->method(self::anything());

        self::assertEquals([], $this->loader->loadResolvedContentVariants([]));
    }

    public function testWhenNoContentVariantsData(): void
    {
        $this->resolvedContentVariantFactory
            ->expects(self::never())
            ->method(self::anything());

        $ids = [101, 201, 301];
        $this->repository
            ->expects(self::once())
            ->method('getContentVariantsData')
            ->with($ids)
            ->willReturn([]);

        self::assertEquals([], $this->loader->loadResolvedContentVariants($ids));
    }

    public function testLoadResolvedContentVariants(): void
    {
        $ids = [101, 102, 201];
        $this->repository
            ->expects(self::once())
            ->method('getContentVariantsData')
            ->with($ids)
            ->willReturn([
                ['id' => 101, 'node' => ['id' => 10]],
                ['id' => 102, 'node' => ['id' => 10]],
                ['id' => 201, 'node' => ['id' => 20]],
            ]);

        self::assertEquals(
            [
                10 => [
                    101 => $this->createResolvedVariant(['id' => 101, 'node' => ['id' => 10]]),
                    102 => $this->createResolvedVariant(['id' => 102, 'node' => ['id' => 10]]),
                ],
                20 => [201 => $this->createResolvedVariant(['id' => 201, 'node' => ['id' => 20]])],
            ],
            $this->loader->loadResolvedContentVariants($ids)
        );
    }

    private function createResolvedVariant(array $data = []): ResolvedContentVariant
    {
        return (new ResolvedContentVariant())->setData($data);
    }
}
