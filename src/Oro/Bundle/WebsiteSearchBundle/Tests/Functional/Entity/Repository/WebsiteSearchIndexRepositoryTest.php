<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository;

/**
 * @dbIsolation
 */
class WebsiteSearchIndexRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            'Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadItemData'
        ]);
    }

    public function testRemoveEntitiesWhenPageEntityIsBeingRemoved()
    {
        $repository = $this->getRepository();
        $repository->removeItemEntities(
            [1],
            'Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\PageEntity',
            null
        );

        $itemsCount = $repository->getCount();

        $this->assertEquals(2, $itemsCount);
    }

    public function testRemoveEntitiesWhenEntityIdsArrayIsEmpty()
    {
        $repository = $this->getRepository();
        $repository->removeItemEntities([], 'll', null);

        $itemsCount = $repository->getCount();

        $this->assertEquals(3, $itemsCount);
    }

    public function testRemoveEntitiesWhenProductEntitiesIsBeingRemoved()
    {
        $repository = $this->getRepository();
        $repository->removeItemEntities(
            [1, 2],
            'Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\ProductEntity',
            'Product_1'
        );

        $itemsCount = $repository->getCount();

        $this->assertEquals(3, $repository->getCount());
    }

    /**
     * @return WebsiteSearchIndexRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'Oro\Bundle\WebsiteSearchBundle\Entity\Item'
        );
    }
}
