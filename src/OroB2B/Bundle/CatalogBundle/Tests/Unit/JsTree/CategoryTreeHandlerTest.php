<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\JsTree;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\JsTree\CategoryTreeHandler;
use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;

class CategoryTreeHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     * */
    protected $managerRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     * */
    protected $repository;

    /**
     * @var CategoryTreeHandler
     */
    protected $categoryTreeHandler;

    /**
     * @var array
     */
    protected $categories = [
        ['id' => 1, 'title' => 'Root', 'parent' => null],
        ['id' => 2, 'title' => 'TV', 'parent' => 1],
        ['id' => 3, 'title' => 'Phones', 'parent' => 1],
        ['id' => 4, 'title' => 'Phone 01', 'parent' => 3],
        ['id' => 5, 'title' => 'Phone 02', 'parent' => 3]
    ];

    /**
     * @var Category[]
     */
    protected $categoriesCollection = [];

    public function setUp()
    {
        $this->managerRegistry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder('OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerRegistry->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BCatalogBundle:Category')
            ->willReturn($this->repository);

        $this->categoryTreeHandler = new CategoryTreeHandler($this->managerRegistry);
    }

    /**
     * @dataProvider moveCategoryDataProvider
     * @param int $nodeId
     * @param int|null $parentNodeId
     * @param int $position
     * @param boolean $withException
     */
    public function testMoveCategory($nodeId, $parentNodeId, $position, $withException)
    {
        $this->prepareCategories($this->categories);
        $categories = $this->categoriesCollection;

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroB2BCatalogBundle:Category')
            ->will($this->returnValue($em));

        $connection->expects($this->once())
            ->method('beginTransaction');

        $currentNode = $categories[$nodeId];
        $parentNode = array_key_exists($parentNodeId, $categories) ? $categories[$parentNodeId] : null ;

        if ($withException) {
            $this->repository->expects($this->at(0))
                ->method('find')
                ->willThrowException(new \Exception());

            $connection->expects($this->once())
                ->method('rollBack');
        } else {
            $this->repository->expects($this->at(0))
                ->method('find')
                ->willReturn($currentNode);

            $this->repository->expects($this->at(1))
                ->method('find')
                ->willReturn($parentNode);

            if ($position) {
                $children = array_values($parentNode->getChildCategories()->toArray());
                $this->repository->expects($this->at(2))
                    ->method('__call')
                    ->with('persistAsNextSiblingOf', [$currentNode, $children[$position - 1]]);
            } else {
                $this->repository->expects($this->at(2))
                    ->method('__call')
                    ->with('persistAsFirstChildOf', [$currentNode, $parentNode]);
            }

            $em->expects($this->at(0))
                ->method('flush');
            $connection->expects($this->once())
                ->method('commit');
        }

        $this->categoryTreeHandler->moveCategory($nodeId, $parentNodeId, $position);
    }

    /**
     * @return array
     */
    public function moveCategoryDataProvider()
    {
        return [
            'move with position' => [
                'nodeId' => 4,
                'parentNodeId' => 1,
                'position' => 1,
                'withException' => false
            ],
            'move without position' => [
                'nodeId' => 5,
                'parentNodeId' => 2,
                'position' => 0,
                'withException' => false
            ],
            'move with exception' => [
                'nodeId' => 5,
                'parentNodeId' => 2,
                'position' => 0,
                'withException' => true
            ],
            'move inside the same category' => [
                'nodeId' => 3,
                'parentNodeId' => 1,
                'position' => 0,
                'withException' => false
            ],
        ];
    }

    /**
     * @dataProvider createTreeDataProvider
     * @param Category[] $categories
     * @param array $expected
     */
    public function testCreateTree($categories, array $expected)
    {
        $this->managerRegistry->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BCatalogBundle:Category')
            ->willReturn($this->repository);

        $this->repository->expects($this->any())
            ->method('getChildrenWithTitles')
            ->with(null, false, 'left', 'ASC')
            ->willReturn($categories);

        $result = $this->categoryTreeHandler->createTree();
        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function createTreeDataProvider()
    {
        $this->prepareCategories($this->categories);

        return [
            'tree' => [
                'categories' => $this->categoriesCollection,
                'expected' => [
                    [
                        'id' => 1,
                        'parent' => '#',
                        'text' => 'Root',
                        'state' => [
                            'opened' => true
                        ]
                    ],
                    [
                        'id' => 2,
                        'parent' => '1',
                        'text' => 'TV',
                        'state' => [
                            'opened' => false
                        ]
                    ],
                    [
                        'id' => 3,
                        'parent' => '1',
                        'text' => 'Phones',
                        'state' => [
                            'opened' => false
                        ]
                    ],
                    [
                        'id' => 4,
                        'parent' => '3',
                        'text' => 'Phone 01',
                        'state' => [
                            'opened' => false
                        ]
                    ],
                    [
                        'id' => 5,
                        'parent' => '3',
                        'text' => 'Phone 02',
                        'state' => [
                            'opened' => false
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @param array $categories
     * @return array
     */
    protected function prepareCategories(array $categories)
    {
        foreach ($categories as $item) {
            $categoryTitle = new LocalizedFallbackValue();
            $categoryTitle->setString($item['title']);

            $category = $this->createCategory($item['id']);
            $category->addTitle($categoryTitle);
            $category->setParentCategory($this->getParent($item['parent']));

            $this->categoriesCollection[$category->getId()] = $category;
        }

        foreach ($this->categoriesCollection as $parentCategory) {
            foreach ($this->categoriesCollection as $category) {
                if ($category->getParentCategory() == $parentCategory) {
                    $parentCategory->addChildCategory($category);
                }
            }
        }
    }

    /**
     * @param int $id
     * @return null
     */
    protected function getParent($id)
    {
        $parent = null;
        foreach ($this->categoriesCollection as $category) {
            if ($category->getId() == $id) {
                $parent = $category;
            }
        }

        return $parent;
    }

    /**
     * @param int $id
     * @return Category
     */
    protected function createCategory($id)
    {
        $category = new Category();

        $reflection = new \ReflectionProperty(get_class($category), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($category, $id);

        return $category;
    }
}
