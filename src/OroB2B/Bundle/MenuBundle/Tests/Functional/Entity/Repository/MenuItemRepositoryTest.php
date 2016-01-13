<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\MenuBundle\Entity\Repository\MenuItemRepository;

/**
 * @dbIsolation
 */
class MenuItemRepositoryTest extends WebTestCase
{
    /**
     * @var MenuItemRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BMenuBundle:MenuItem');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\MenuBundle\Tests\Functional\DataFixtures\LoadMenuItemData'
            ]
        );
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
        $expected = [
            $this->getReference('menu_item.1'),
            $this->getReference('menu_item.4')
        ];
        $actual = $this->repository->findRoots();

        $this->assertCount(2, $actual);

        foreach ($actual as $item) {
            $this->assertContains($item, $expected);
        }
    }
}
