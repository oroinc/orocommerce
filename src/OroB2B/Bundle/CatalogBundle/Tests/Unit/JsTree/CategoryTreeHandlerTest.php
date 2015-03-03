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

        $this->categoryTreeHandler = new CategoryTreeHandler($this->managerRegistry);
    }

    /**
     * @dataProvider createTreeDataProvider
     * @param Category[] $categories
     * @param int $selectedCategoryId
     * @param array $expected
     */
    public function testCreateTree($categories, $selectedCategoryId, array $expected)
    {
        $this->managerRegistry->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BCatalogBundle:Category')
            ->willReturn($this->repository);

        $this->repository->expects($this->any())
            ->method('getChildren')
            ->with(null, false, 'left', 'ASC')
            ->willReturn($categories);

        $result = $this->categoryTreeHandler->createTree($selectedCategoryId);
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
            'without selected item' => [
                'categories' => $this->categoriesCollection,
                'selectedCategoryId' => null,
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
            ],
            'with selected item' => [
                'categories' => $this->categoriesCollection,
                'selectedCategoryId' => 2,
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

            $this->categoriesCollection[] = $category;
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
