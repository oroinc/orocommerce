<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedContentNodesMerger;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;

class ResolvedContentNodesMergerTest extends \PHPUnit\Framework\TestCase
{
    private ResolvedContentNodesMerger $merger;

    protected function setUp(): void
    {
        $this->merger = new ResolvedContentNodesMerger();
    }

    /**
     * @dataProvider resolvedNodesDataProvider
     *
     * @param ResolvedContentNode[] $resolvedContentNodes
     * @param ResolvedContentNode[] $expected
     */
    public function testMergeResolvedNodes(array $resolvedContentNodes, array $expected): void
    {
        self::assertEquals($expected, $this->merger->mergeResolvedNodes($resolvedContentNodes));
    }

    public function resolvedNodesDataProvider(): array
    {
        $resolvedNode1Scope1 = $this->createResolvedNode(
            1,
            'Node 1 Scope 1',
            [
                $this->createResolvedNode(11, 'Node 11 Scope 1'),
                $this->createResolvedNode(12, 'Node 12 Scope 1', [$this->createResolvedNode(121, 'Node 121 Scope 1')]),
                $this->createResolvedNode(13, 'Node 13 Scope 1'),
            ]
        );

        $resolvedNode1Scope2 = $this->createResolvedNode(
            1,
            'Node 1 Scope 2',
            [
                $this->createResolvedNode(12, 'Node 12 Scope 2', [$this->createResolvedNode(122, 'Node 122 Scope 2')]),
                $this->createResolvedNode(14, 'Node 14 Scope 2'),
            ]
        );

        $mergedResolvedNode1plus2 = $this->createResolvedNode(
            1,
            'Node 1 Scope 1',
            [
                $this->createResolvedNode(11, 'Node 11 Scope 1'),
                $this->createResolvedNode(
                    12,
                    'Node 12 Scope 1',
                    [
                        $this->createResolvedNode(121, 'Node 121 Scope 1'),
                        $this->createResolvedNode(122, 'Node 122 Scope 2'),
                    ]
                ),
                $this->createResolvedNode(13, 'Node 13 Scope 1'),
                $this->createResolvedNode(14, 'Node 14 Scope 2'),
            ]
        );

        $mergedResolvedNode2plus1 = $this->createResolvedNode(
            1,
            'Node 1 Scope 2',
            [
                $this->createResolvedNode(11, 'Node 11 Scope 1'),
                $this->createResolvedNode(
                    12,
                    'Node 12 Scope 2',
                    [
                        $this->createResolvedNode(121, 'Node 121 Scope 1'),
                        $this->createResolvedNode(122, 'Node 122 Scope 2'),
                    ]
                ),
                $this->createResolvedNode(13, 'Node 13 Scope 1'),
                $this->createResolvedNode(14, 'Node 14 Scope 2'),
            ]
        );

        return [
            'empty array' => [
                'resolvedContentNodes' => [],
                'expected' => [],
            ],
            '1 resolved node' => [
                'resolvedContentNodes' => [$resolvedNode1Scope1],
                'expected' => [$resolvedNode1Scope1->getId() => $resolvedNode1Scope1],
            ],
            '1+2 resolved node' => [
                'resolvedContentNodes' => [$resolvedNode1Scope1, $resolvedNode1Scope2],
                'expected' => [$mergedResolvedNode1plus2->getId() => $mergedResolvedNode1plus2],
            ],
            '2+1 resolved node' => [
                'resolvedContentNodes' => [$resolvedNode1Scope2, $resolvedNode1Scope1],
                'expected' => [$mergedResolvedNode2plus1->getId() => $mergedResolvedNode2plus1],
            ],
        ];
    }

    private function createResolvedNode(
        int $id,
        string $resolvedContentVariantTitle,
        array $childNodes = []
    ): ResolvedContentNode {
        $resolvedNode = new ResolvedContentNode(
            $id,
            'sample_identifier_' . $id,
            $id,
            new ArrayCollection(),
            new ResolvedContentVariant()
        );

        $resolvedNode->getResolvedContentVariant()->title = $resolvedContentVariantTitle;

        foreach ($childNodes as $childNode) {
            $resolvedNode->addChildNode($childNode);
        }

        return $resolvedNode;
    }
}
