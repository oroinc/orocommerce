<?php

namespace Oro\Component\Tree\Tests\Unit\Handler;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\UIBundle\Model\TreeItem;
use Oro\Component\Tree\Entity\Repository\NestedTreeRepository;
use Oro\Component\Tree\Tests\Unit\Stubs\EntityStub;
use Oro\Component\Tree\Tests\Unit\Stubs\Handler\TreeHandlerStub;

class AbstractTreeHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TreeHandlerStub */
    protected $treeHandler;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $em */
        $this->em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
        $registry = $this->getMockBuilder(ManagerRegistry::class)->getMock();
        $registry
            ->expects($this->any())
            ->method('getManagerForClass')
            ->with(EntityStub::class)
            ->willReturn($this->em);

        $this->treeHandler = new TreeHandlerStub(EntityStub::class, $registry);
    }

    /**
     * @dataProvider getTreeDataProvider
     *
     * @param bool       $includeRoot
     * @param EntityStub $root
     * @param array      $children
     * @param array      $expectedTree
     */
    public function testCreateTree($includeRoot, $root, $children, $expectedTree)
    {
        $nodes = $children;
        if ($includeRoot) {
            $nodes = array_merge([$root], $nodes);
        }

        /** @var NestedTreeRepository|\PHPUnit\Framework\MockObject\MockObject $repository */
        $repository = $this->getMockBuilder(NestedTreeRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())
            ->method('getChildren')
            ->with($root, false, 'left', 'ASC', $includeRoot)
            ->willReturn($nodes);

        $this->em
            ->expects($this->once())
            ->method('getRepository')
            ->with(EntityStub::class)
            ->willReturn($repository);

        $this->assertEquals(
            $expectedTree,
            $this->treeHandler->createTree($root, $includeRoot)
        );
    }

    /**
     * @dataProvider getTreeItemListDataProvider
     *
     * @param bool       $includeRoot
     * @param EntityStub $root
     * @param array      $children
     * @param array      $expectedTreeItemList
     */
    public function testGetTreeItemList($includeRoot, $root, $children, $expectedTreeItemList)
    {
        $nodes = $children;
        if ($includeRoot) {
            $nodes = array_merge([$root], $nodes);
        }

        /** @var NestedTreeRepository|\PHPUnit\Framework\MockObject\MockObject $repository */
        $repository = $this->getMockBuilder(NestedTreeRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())
            ->method('getChildren')
            ->with($root, false, 'left', 'ASC', $includeRoot)
            ->willReturn($nodes);

        $this->em
            ->expects($this->once())
            ->method('getRepository')
            ->with(EntityStub::class)
            ->willReturn($repository);

        $this->assertEquals(
            $expectedTreeItemList,
            $this->treeHandler->getTreeItemList($root, $includeRoot)
        );
    }

    /**
     * @return array
     */
    public function getTreeDataProvider()
    {
        $root = $this->getEntity(1, 'Root');
        $children = [
            $this->getEntity(2, 'Item 1', 1),
            $this->getEntity(3, 'Item 1-1', 2),
            $this->getEntity(4, 'Item 2', 1),
        ];

        return [
            'include root' => [
                'includeRoot' => true,
                'root' => $root,
                'children' => $children,
                'expectedTree' => [
                    [
                        'id' => 1,
                        'parent' => '#',
                        'text' => 'Root'
                    ],
                    [
                        'id' => 2,
                        'parent' => 1,
                        'text' => 'Item 1',
                    ],
                    [
                        'id' => 3,
                        'parent' => 2,
                        'text' => 'Item 1-1',
                    ],
                    [
                        'id' => 4,
                        'parent' => 1,
                        'text' => 'Item 2',
                    ]
                ]
            ],
            'not include root' => [
                'includeRoot' => false,
                'root' => $root,
                'children' => $children,
                'expectedTree' => [
                    [
                        'id' => 2,
                        'parent' => '#',
                        'text' => 'Item 1',
                    ],
                    [
                        'id' => 3,
                        'parent' => 2,
                        'text' => 'Item 1-1',
                    ],
                    [
                        'id' => 4,
                        'parent' => '#',
                        'text' => 'Item 2',
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function getTreeItemListDataProvider()
    {
        $root = $this->getEntity(1, 'Root');
        $children = [
            $this->getEntity(2, 'Item 1', 1),
            $this->getEntity(3, 'Item 1-1', 2),
            $this->getEntity(4, 'Item 2', 1),
        ];

        return [
            'include root' => [
                'includeRoot' => true,
                'root' => $root,
                'children' => $children,
                'expectedTreeItemList' => $this->getTreeItemList(true)
            ],
            'not include root' => [
                'includeRoot' => false,
                'root' => $root,
                'children' => $children,
                'expectedTreeItemList' => $this->getTreeItemList(false)
            ]
        ];
    }

    /**
     * @param int      $id
     * @param string   $text
     * @param int|null $parent
     *
     * @return EntityStub
     */
    protected function getEntity($id, $text, $parent = null)
    {
        $entity = new EntityStub();
        $entity->id = $id;
        $entity->text = $text;
        $entity->parent = $parent;

        return $entity;
    }

    /**
     * @param bool $includeRoot
     *
     * @return array
     */
    protected function getTreeItemList($includeRoot)
    {
        $item1 = new TreeItem(2, 'Item 1');
        $item11 = new TreeItem(3, 'Item 1-1');
        $item11->setParent($item1);
        $item2 = new TreeItem(4, 'Item 2');

        $list = [
            2 => $item1,
            3 => $item11,
            4 => $item2
        ];
        if ($includeRoot) {
            $root = new TreeItem(1, 'Root');
            $item1->setParent($root);
            $item2->setParent($root);

            $list[1] = $root;
            ksort($list);
        }

        return $list;
    }
}
