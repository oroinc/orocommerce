<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentNodeUtils\Loader;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Factory\ResolvedContentNodeFactory;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Loader\ResolvedContentNodesLoader;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Loader\ResolvedContentVariantsLoader;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;

class ResolvedContentNodesLoaderTest extends \PHPUnit\Framework\TestCase
{
    private ResolvedContentVariantsLoader|\PHPUnit\Framework\MockObject\MockObject $resolvedContentVariantsLoader;

    private ResolvedContentNodesLoader $loader;

    private ContentNodeRepository|\PHPUnit\Framework\MockObject\MockObject $repository;

    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->resolvedContentVariantsLoader = $this->createMock(ResolvedContentVariantsLoader::class);
        $resolvedContentNodeFactory = $this->createMock(ResolvedContentNodeFactory::class);
        $this->loader = new ResolvedContentNodesLoader(
            $managerRegistry,
            $this->resolvedContentVariantsLoader,
            $resolvedContentNodeFactory
        );

        $this->repository = $this->createMock(ContentNodeRepository::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->with(ContentNode::class)
            ->willReturn($this->repository);

        $resolvedContentNodeFactory
            ->expects(self::any())
            ->method('createFromArray')
            ->willReturnCallback(fn (array $data) => $this->createResolvedNode($data));
    }

    public function testWhenNoContentNodeIds(): void
    {
        $this->resolvedContentVariantsLoader
            ->expects(self::never())
            ->method(self::anything());

        self::assertEquals([], $this->loader->loadResolvedContentNodes([]));
    }

    public function testWhenNoContentNodesData(): void
    {
        $this->resolvedContentVariantsLoader
            ->expects(self::never())
            ->method(self::anything());

        $ids = [10, 20, 30];
        $this->repository
            ->expects(self::once())
            ->method('getContentNodesData')
            ->with($ids)
            ->willReturn([]);

        self::assertEquals([], $this->loader->loadResolvedContentNodes($ids));
    }

    /**
     * @dataProvider loadResolvedContentNodesWhenNoContentVariantIdsDataProvider
     */
    public function testLoadResolvedContentNodesWhenNoContentVariantIds(array $contentNodesData, array $expected): void
    {
        $this->resolvedContentVariantsLoader
            ->expects(self::never())
            ->method(self::anything());

        $ids = [10, 20, 30];
        $this->repository
            ->expects(self::once())
            ->method('getContentNodesData')
            ->with($ids)
            ->willReturn($contentNodesData);

        self::assertEquals($expected, $this->loader->loadResolvedContentNodes($ids));
    }

    public function loadResolvedContentNodesWhenNoContentVariantIdsDataProvider(): array
    {
        return [
            'content node without children' => [
                'contentNodesData' => [['id' => 10, 'parentNode' => null]],
                'expected' => [
                    10 => $this->createResolvedNode(['id' => 10, 'contentVariant' => $this->createResolvedVariant()]),
                ],
            ],
            'content node with children' => [
                'contentNodesData' => [
                    ['id' => 10, 'parentNode' => null],
                    ['id' => 20, 'parentNode' => ['id' => 10]],
                    ['id' => 30, 'parentNode' => ['id' => 10]],
                ],
                'expected' => [
                    10 => $this->createResolvedNode(['id' => 10, 'contentVariant' => $this->createResolvedVariant()])
                        ->addChildNode(
                            $this->createResolvedNode(['id' => 20, 'contentVariant' => $this->createResolvedVariant()])
                        )
                        ->addChildNode(
                            $this->createResolvedNode(['id' => 30, 'contentVariant' => $this->createResolvedVariant()])
                        ),
                ],
            ],
            '2 base content nodes' => [
                'contentNodesData' => [
                    ['id' => 10, 'parentNode' => null],
                    ['id' => 20, 'parentNode' => ['id' => 10]],
                    ['id' => 30],
                ],
                'expected' => [
                    10 => $this->createResolvedNode(['id' => 10, 'contentVariant' => $this->createResolvedVariant()])
                        ->addChildNode(
                            $this->createResolvedNode(['id' => 20, 'contentVariant' => $this->createResolvedVariant()])
                        ),
                    30 => $this->createResolvedNode(['id' => 30, 'contentVariant' => $this->createResolvedVariant()]),
                ],
            ],
        ];
    }

    /**
     * @dataProvider loadResolvedContentNodesWhenHasContentVariantIdsDataProvider
     */
    public function testWhenHasContentVariantIds(array $contentNodesData, array $expected): void
    {
        $this->resolvedContentVariantsLoader
            ->expects(self::any())
            ->method('loadResolvedContentVariants')
            ->willReturn([
                10 => [
                    101 => $this->createResolvedVariant(['id' => 101]),
                    102 => $this->createResolvedVariant(['id' => 102]),
                ],
                20 => [201 => $this->createResolvedVariant(['id' => 201])],
            ]);

        $ids = [10 => 101, 20 => 201, 30 => 301];
        $this->repository
            ->expects(self::once())
            ->method('getContentNodesData')
            ->with(array_keys($ids))
            ->willReturn($contentNodesData);

        self::assertEquals($expected, $this->loader->loadResolvedContentNodes($ids));
    }

    public function loadResolvedContentNodesWhenHasContentVariantIdsDataProvider(): array
    {
        return [
            'content node without children' => [
                'contentNodesData' => [['id' => 10, 'parentNode' => null]],
                'expected' => [
                    10 => $this->createResolvedNode(
                        ['id' => 10, 'contentVariant' => $this->createResolvedVariant(['id' => 101])]
                    ),
                ],
            ],
            'content node with children' => [
                'contentNodesData' => [
                    ['id' => 10, 'parentNode' => null],
                    ['id' => 20, 'parentNode' => ['id' => 10]],
                    ['id' => 30, 'parentNode' => ['id' => 10]],
                ],
                'expected' => [
                    10 => $this->createResolvedNode(
                        ['id' => 10, 'contentVariant' => $this->createResolvedVariant(['id' => 101])]
                    )
                        ->addChildNode(
                            $this->createResolvedNode(
                                ['id' => 20, 'contentVariant' => $this->createResolvedVariant(['id' => 201])]
                            )
                        )
                        ->addChildNode(
                            $this->createResolvedNode(['id' => 30, 'contentVariant' => $this->createResolvedVariant()])
                        ),
                ],
            ],
            '2 base content nodes' => [
                'contentNodesData' => [
                    ['id' => 10, 'parentNode' => null],
                    ['id' => 20, 'parentNode' => ['id' => 10]],
                    ['id' => 30],
                ],
                'expected' => [
                    10 => $this->createResolvedNode(
                        ['id' => 10, 'contentVariant' => $this->createResolvedVariant(['id' => 101])]
                    )
                        ->addChildNode(
                            $this->createResolvedNode(
                                ['id' => 20, 'contentVariant' => $this->createResolvedVariant(['id' => 201])]
                            )
                        ),
                    30 => $this->createResolvedNode(['id' => 30, 'contentVariant' => $this->createResolvedVariant()]),
                ],
            ],
        ];
    }

    private function createResolvedNode(array $data): ResolvedContentNode
    {
        return new ResolvedContentNode(
            $data['id'],
            'root__' . $data['id'],
            0,
            new ArrayCollection(),
            $data['contentVariant']
        );
    }

    private function createResolvedVariant(array $data = []): ResolvedContentVariant
    {
        return (new ResolvedContentVariant())->setData($data);
    }
}
