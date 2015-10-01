<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\JsTree;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Storage\CategoryVisibilityData;
use OroB2B\Bundle\AccountBundle\Storage\CategoryVisibilityStorage;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\CatalogBundle\JsTree\CategoryTreeHandler;
use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;

class CategoryTreeHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $managerRegistry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|CategoryRepository */
    protected $repository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject|CategoryVisibilityStorage */
    protected $categoryVisibilityStorage;

    /** @var CategoryTreeHandler */
    protected $categoryTreeHandler;

    /** @var array */
    protected $categories = [
        ['id' => 1, 'title' => 'Root', 'parent' => null],
        ['id' => 2, 'title' => 'TV', 'parent' => 1],
        ['id' => 3, 'title' => 'Phones', 'parent' => 1],
        ['id' => 4, 'title' => 'LCD TV', 'parent' => 2],
        ['id' => 5, 'title' => 'Plasma TV', 'parent' => 2],
        ['id' => 6, 'title' => 'FullHD TV', 'parent' => 4],
        ['id' => 7, 'title' => '4K TV', 'parent' => 4],
        ['id' => 8, 'title' => '3D TV', 'parent' => 5],
        ['id' => 9, 'title' => 'QHD TV', 'parent' => 5],
        ['id' => 10, 'title' => 'Smartphone', 'parent' => 3],
        ['id' => 11, 'title' => 'Mobile pones', 'parent' => 3],
        ['id' => 12, 'title' => 'Phone 01', 'parent' => 10],
        ['id' => 13, 'title' => 'Phone 02', 'parent' => 10],
        ['id' => 14, 'title' => 'Phone 03', 'parent' => 11],
        ['id' => 15, 'title' => 'Phone 04', 'parent' => 11],
    ];

    /**
     * @var Category[]
     */
    protected $categoriesCollection = [];

    public function setUp()
    {
        $this->repository = $this->getMockBuilder('OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerRegistry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->managerRegistry->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BCatalogBundle:Category')
            ->willReturn($this->repository);

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryVisibilityStorage = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Storage\CategoryVisibilityStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryTreeHandler = new CategoryTreeHandler(
            'OroB2BCatalogBundle:Category',
            $this->managerRegistry,
            $this->securityFacade,
            $this->categoryVisibilityStorage
        );
    }

    protected function tearDown()
    {
        unset(
            $this->managerRegistry,
            $this->repository,
            $this->securityFacade,
            $this->categoryVisibilityStorage,
            $this->categoryTreeHandler
        );
    }

    /**
     * @dataProvider moveNodeDataProvider
     * @param int $nodeId
     * @param int|null $parentNodeId
     * @param int $position
     * @param boolean $withException
     */
    public function testMoveNode($nodeId, $parentNodeId, $position, $withException)
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

        $this->categoryTreeHandler->moveNode($nodeId, $parentNodeId, $position);
    }

    /**
     * @return array
     */
    public function moveNodeDataProvider()
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
     *
     * @param Category[] $categories
     * @param array $expected
     * @param CategoryVisibilityData $categoryVisibilityData
     * @param UserInterface|null $user
     */
    public function testCreateTree(
        $categories,
        array $expected,
        CategoryVisibilityData $categoryVisibilityData = null,
        UserInterface $user = null
    ) {
        $this->managerRegistry->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BCatalogBundle:Category')
            ->willReturn($this->repository);

        $this->repository->expects($this->any())
            ->method('getChildrenWithTitles')
            ->with(null, false, 'left', 'ASC')
            ->willReturn($categories);

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user);

        $this->categoryVisibilityStorage->expects($categoryVisibilityData ? $this->once() : $this->never())
            ->method('getCategoryVisibilityData')
            ->with($user instanceof AccountUser ? $user->getAccount()->getId() : null)
            ->willReturn($categoryVisibilityData);

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
            'tree for backend user' => [
                'categories' => $this->categoriesCollection,
                'expected' => [
                    ['id' => 1, 'text' => 'Root', 'parent' => '#', 'state' => ['opened' => true]],
                    ['id' => 2, 'text' => 'TV', 'parent' => '1', 'state' => ['opened' => false]],
                    ['id' => 3, 'text' => 'Phones', 'parent' => '1', 'state' => ['opened' => false]],
                    ['id' => 4, 'text' => 'LCD TV', 'parent' => '2', 'state' => ['opened' => false]],
                    ['id' => 5, 'text' => 'Plasma TV', 'parent' => '2', 'state' => ['opened' => false]],
                    ['id' => 6, 'text' => 'FullHD TV', 'parent' => '4', 'state' => ['opened' => false]],
                    ['id' => 7, 'text' => '4K TV', 'parent' => '4', 'state' => ['opened' => false]],
                    ['id' => 8, 'text' => '3D TV', 'parent' => '5', 'state' => ['opened' => false]],
                    ['id' => 9, 'text' => 'QHD TV', 'parent' => '5', 'state' => ['opened' => false]],
                    ['id' => 10, 'text' => 'Smartphone', 'parent' => '3', 'state' => ['opened' => false]],
                    ['id' => 11, 'text' => 'Mobile pones', 'parent' => '3', 'state' => ['opened' => false]],
                    ['id' => 12, 'text' => 'Phone 01', 'parent' => '10', 'state' => ['opened' => false]],
                    ['id' => 13, 'text' => 'Phone 02', 'parent' => '10', 'state' => ['opened' => false]],
                    ['id' => 14, 'text' => 'Phone 03', 'parent' => '11', 'state' => ['opened' => false]],
                    ['id' => 15, 'text' => 'Phone 04', 'parent' => '11', 'state' => ['opened' => false]]
                ],
                'categoryVisibilityData' => null,
                'user' => new User()
            ],
            'tree for anonymous user with visible ids' => [
                'categories' => $this->categoriesCollection,
                'expected' => [
                    ['id' => 1, 'text' => 'Root', 'parent' => '#', 'state' => ['opened' => true]],
                    ['id' => 2, 'text' => 'TV', 'parent' => '1', 'state' => ['opened' => false]]
                ],
                'categoryVisibilityData' => new CategoryVisibilityData([1,2, 6,7,12,13,14,15], true),
                'user' => null
            ],
            'tree for account user with invisible ids' => [
                'categories' => $this->categoriesCollection,
                'expected' => [
                    ['id' => 1, 'text' => 'Root', 'parent' => '#', 'state' => ['opened' => true]],
                    ['id' => 2, 'text' => 'TV', 'parent' => '1', 'state' => ['opened' => false]],
                    ['id' => 4, 'text' => 'LCD TV', 'parent' => '2', 'state' => ['opened' => false]],
                    ['id' => 5, 'text' => 'Plasma TV', 'parent' => '2', 'state' => ['opened' => false]],
                    ['id' => 6, 'text' => 'FullHD TV', 'parent' => '4', 'state' => ['opened' => false]],
                    ['id' => 7, 'text' => '4K TV', 'parent' => '4', 'state' => ['opened' => false]],
                    ['id' => 8, 'text' => '3D TV', 'parent' => '5', 'state' => ['opened' => false]],
                    ['id' => 9, 'text' => 'QHD TV', 'parent' => '5', 'state' => ['opened' => false]],
                ],
                'categoryVisibilityData' => new CategoryVisibilityData([3], false),
                'user' => (new AccountUser)
                    ->setAccount($this->createEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 42))
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

            $category = $this->createEntity('OroB2B\Bundle\CatalogBundle\Entity\Category', $item['id']);
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
            if ($category->getId() === $id) {
                $parent = $category;
            }
        }

        return $parent;
    }
}
