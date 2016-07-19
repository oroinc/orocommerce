<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Functional\Entity\Repository;

use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\MenuBundle\Entity\Repository\MenuItemRepository;

/**
 * @dbIsolation
 */
class MenuItemRepositoryTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var MenuItemRepository
     */
    protected $repository;

    public function setUp()
    {
        $this->initClient();

        $this->loadFixtures(['OroB2B\Bundle\MenuBundle\Tests\Functional\DataFixtures\LoadMenuItemData']);
        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BMenuBundle:MenuItem');
        $this->repository = $this->em->getRepository('OroB2BMenuBundle:MenuItem');
    }

    /**
     * @dataProvider findMenuItemByTitleDataProvider
     * @param string $title
     * @param string|null $expectedData
     */
    public function testFindMenuItemByTitle($title, $expectedData)
    {
        if ($expectedData) {
            $expectedData = $this->getReference($expectedData);
        }
        $this->assertEquals($expectedData, $this->repository->findMenuItemByTitle($title));
    }

    /**
     * @return array
     */
    public function findMenuItemByTitleDataProvider()
    {
        return [
            [
                'title' => 'menu_item.4',
                'expectedData' => 'menu_item.4',
            ],
            [
                'title' => 'not exists',
                'expectedData' => null,
            ]
        ];
    }

    /**
     * @dataProvider findMenuItemWithChildrenAndTitleByTitleDataProvider
     * @param $title
     * @param $expectedData
     */
    public function testFindMenuItemWithChildrenAndTitleByTitle($title, $expectedData)
    {
        $queryAnalyzer = new QueryAnalyzer($this->em->getConnection()->getDatabasePlatform());

        $prevLogger = $this->em->getConnection()->getConfiguration()->getSQLLogger();
        $this->em->getConnection()->getConfiguration()->setSQLLogger($queryAnalyzer);

        /** @var MenuItem $result */
        $result = $this->repository->findMenuItemWithChildrenAndTitleByTitle($title);

        $this->assertTreeEquals($expectedData, $result);

        $queries = $queryAnalyzer->getExecutedQueries();
        $this->assertCount(1, $queries);

        $this->em->getConnection()->getConfiguration()->setSQLLogger($prevLogger);
    }

    /**
     * @return array
     */
    public function findMenuItemWithChildrenAndTitleByTitleDataProvider()
    {
        return [
            [
                'title' => 'menu_item.4',
                'expectedData' => [
                    'menu_item.4' => [
                        'menu_item.4_5' => [
                            'menu_item.4_5_6' => [
                                'menu_item.4_5_6_8' => []
                            ],
                            'menu_item.4_5_7' => [],
                        ]
                    ],
                ]
            ],
            [
                'title' => 'not exists',
                'expectedData' => null,
            ]
        ];
    }

    /**
     * @param $expectedData
     * @param MenuItem|null $root
     */
    protected function assertTreeEquals($expectedData, MenuItem $root = null)
    {
        if (!$expectedData) {
            $this->assertEquals($expectedData, $root);
        } else {
            $this->assertEquals($expectedData, $this->prepareActualData($root));
        }
    }

    /**
     * @param MenuItem $root
     * @return array
     */
    protected function prepareActualData(MenuItem $root)
    {
        $tree = [];
        foreach ($root->getChildren() as $child) {
            $tree = array_merge($tree, $this->prepareActualData($child));
        }
        return [$root->getDefaultTitle()->getString() => $tree];
    }

    public function testFindRootByDefaultTitle()
    {
        /** @var MenuItem $menu */
        $menu = $this->getReference('menu_item.1');
        $title = $menu->getDefaultTitle()->getString();
        $actual = $this->repository->findRootByDefaultTitle($title);

        $this->assertEquals($menu, $actual);
    }

    public function testFindRoots()
    {
        $initCount = count($this->repository->findRoots());

        $menuItem = new MenuItem();
        $menuItem->setDefaultTitle('test');

        $this->em->persist($menuItem);
        $this->em->flush();

        $actual = $this->repository->findRoots();

        $this->assertCount($initCount + 1, $actual);

        $this->assertContains($menuItem, $actual);
    }
}
