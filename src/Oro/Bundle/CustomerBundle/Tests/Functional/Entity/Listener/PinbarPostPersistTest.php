<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Entity\Listener;

use Oro\Bundle\CustomerBundle\Entity\NavigationItem;
use Oro\Bundle\CustomerBundle\Entity\PinbarTab;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadNavigationItemData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class PinbarPostPersistTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadNavigationItemData::class]);
    }

    public function testIncrementTabsPostition()
    {
        /** @var NavigationItem $item1 */
        $item1 = $this->getReference(LoadNavigationItemData::ITEM_1);
        /** @var NavigationItem $item2 */
        $item2 = $this->getReference(LoadNavigationItemData::ITEM_2);
        /** @var NavigationItem $item3 */
        $item3 = $this->getReference(LoadNavigationItemData::ITEM_3);

        $this->assertEquals(1, $item1->getPosition());
        $this->assertEquals(2, $item2->getPosition());
        $this->assertEquals(3, $item3->getPosition());

        $pinbarTab = new PinbarTab();
        $pinbarTab->setItem($item1);

        $doctrineHelper = self::getContainer()->get('oro_entity.doctrine_helper');
        $pinbarEm = $doctrineHelper->getEntityManager(PinbarTab::class);
        $pinbarEm->persist($pinbarTab);
        $pinbarEm->flush();

        $navItemsEm = $doctrineHelper->getEntityManager(NavigationItem::class);
        $navItemsEm->refresh($item1);
        $navItemsEm->refresh($item2);
        $navItemsEm->refresh($item3);

        $this->assertEquals(1, $item1->getPosition());
        $this->assertEquals(3, $item2->getPosition());
        $this->assertEquals(4, $item3->getPosition());
    }
}
