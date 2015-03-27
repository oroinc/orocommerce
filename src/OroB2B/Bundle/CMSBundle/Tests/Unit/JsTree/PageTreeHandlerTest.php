<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Unit\JsTree;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use OroB2B\Bundle\CMSBundle\Entity\Page;
use OroB2B\Bundle\CMSBundle\JsTree\PageTreeHandler;
use OroB2B\Bundle\RedirectBundle\Manager\SlugManager;

class PageTreeHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     * */
    protected $managerRegistry;

    /**
     * @var SlugManager
     */
    protected $slugManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     * */
    protected $repository;

    /**
     * @var PageTreeHandler
     */
    protected $pageTreeHandler;

    /**
     * @var array
     */
    protected $pages = [
        ['id' => 1, 'title' => 'First Root', 'parent' => null],
        ['id' => 2, 'title' => 'Scaled models', 'parent' => 1],
        ['id' => 3, 'title' => 'Traines', 'parent' => 1],
        ['id' => 4, 'title' => 'Train model 01', 'parent' => 3],
        ['id' => 5, 'title' => 'Train model 02', 'parent' => 3],
        ['id' => 6, 'title' => 'Second Root', 'parent' => null],
    ];

    /**
     * @var Page[]
     */
    protected $pagesCollection = [];

    public function setUp()
    {
        $this->managerRegistry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder('OroB2B\Bundle\CMSBundle\Entity\Repository\PageRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerRegistry->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BCMSBundle:Page')
            ->willReturn($this->repository);

        $this->slugManager = $this->getMockBuilder('OroB2B\Bundle\RedirectBundle\Manager\SlugManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageTreeHandler = new PageTreeHandler(
            'OroB2BCMSBundle:Page',
            $this->managerRegistry,
            $this->slugManager
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
        $this->preparePages($this->pages);
        $pages = $this->pagesCollection;

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
            ->with('OroB2BCMSBundle:Page')
            ->will($this->returnValue($em));

        $connection->expects($this->once())
            ->method('beginTransaction');

        $currentNode = $pages[$nodeId];
        $parentNode = array_key_exists($parentNodeId, $pages) ? $pages[$parentNodeId] : null ;

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

            if ('#' !== $parentNodeId) {
                if ($position) {
                    $children = array_values($parentNode->getChildpages()->toArray());
                    $this->repository->expects($this->at(2))
                        ->method('__call')
                        ->with('persistAsNextSiblingOf', [$currentNode, $children[$position - 1]]);
                } else {
                    $this->repository->expects($this->at(2))
                        ->method('__call')
                        ->with('persistAsFirstChildOf', [$currentNode, $parentNode]);
                }
            }

            $this->slugManager->expects($this->once())
                ->method('makeUrlUnique')
                ->with($currentNode->getCurrentSlug());

            $em->expects($this->at(0))
                ->method('flush');
            $connection->expects($this->once())
                ->method('commit');
        }

        $this->pageTreeHandler->moveNode($nodeId, $parentNodeId, $position);

        if (!$withException) {
            $this->assertEquals($parentNode, $currentNode->getParentPage());
        }
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
            'move inside the same page' => [
                'nodeId' => 3,
                'parentNodeId' => 1,
                'position' => 0,
                'withException' => false
            ],
            'move to root level' => [
                'nodeId' => 3,
                'parentNodeId' => '#',
                'position' => 0,
                'withException' => false
            ],
            'move on root level' => [
                'nodeId' => 3,
                'parentNodeId' => '#',
                'position' => 1,
                'withException' => false
            ],
        ];
    }

    /**
     * @dataProvider createTreeDataProvider
     * @param Page[] $pages
     * @param array $expected
     */
    public function testCreateTree($pages, array $expected)
    {
        $this->managerRegistry->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BCMSBundle:Page')
            ->willReturn($this->repository);

        $this->repository->expects($this->any())
            ->method('getChildren')
            ->with(null, false, 'left', 'ASC')
            ->willReturn($pages);

        $result = $this->pageTreeHandler->createTree();
        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function createTreeDataProvider()
    {
        $this->preparePages($this->pages);

        return [
            'tree' => [
                'pages' => $this->pagesCollection,
                'expected' => [
                    [
                        'id'     => 1,
                        'parent' => '#',
                        'text'   => 'First Root',
                        'state'  => [
                            'opened' => true
                        ]
                    ],
                    [
                        'id'     => 6,
                        'parent' => '#',
                        'text'   => 'Second Root',
                        'state'  => [
                            'opened' => true
                        ]
                    ],
                    [
                        'id'     => 2,
                        'parent' => '1',
                        'text'   => 'Scaled models',
                        'state'  => [
                            'opened' => false
                        ]
                    ],
                    [
                        'id'     => 3,
                        'parent' => '1',
                        'text'   => 'Traines',
                        'state'  => [
                            'opened' => false
                        ]
                    ],
                    [
                        'id'     => 4,
                        'parent' => '3',
                        'text'   => 'Train model 01',
                        'state'  => [
                            'opened' => false
                        ]
                    ],
                    [
                        'id'     => 5,
                        'parent' => '3',
                        'text'   => 'Train model 02',
                        'state'  => [
                            'opened' => false
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @param array $pages
     * @return array
     */
    protected function preparePages(array $pages)
    {
        foreach ($pages as $item) {
            $page = $this->createPage($item['id']);
            $page->setTitle($item['title']);
            $page->setParentPage($this->getParent($item['parent']));

            $this->pagesCollection[$page->getId()] = $page;
        }

        foreach ($this->pagesCollection as $parentPage) {
            foreach ($this->pagesCollection as $page) {
                if ($page->getParentPage() == $parentPage) {
                    $parentPage->addChildPage($page);
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
        foreach ($this->pagesCollection as $page) {
            if ($page->getId() == $id) {
                $parent = $page;
            }
        }

        return $parent;
    }

    /**
     * @param int $id
     * @return Page
     */
    protected function createPage($id)
    {
        $page = new Page();

        $reflection = new \ReflectionProperty(get_class($page), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($page, $id);

        return $page;
    }
}
