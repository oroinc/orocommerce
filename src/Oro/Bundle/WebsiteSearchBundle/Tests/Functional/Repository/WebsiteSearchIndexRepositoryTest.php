<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadIndexItems;

/**
 * @dbIsolation
 */
class WebsiteSearchIndexRepositoryTest extends WebTestCase
{
    /** @var WebsiteSearchIndexRepository */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadIndexItems::class]);
        $helper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $this->repository = $helper->getEntityRepository(Item::class);
    }

    public function testRemoveIndexByAlias()
    {
        $this->repository->removeIndexByAlias(LoadIndexItems::ALIAS_REAL);
        $realAliasesLeft = $this->repository->findBy(['alias' => LoadIndexItems::ALIAS_REAL]);
        $this->assertCount(0, $realAliasesLeft);
    }

    public function testRenameIndexAlias()
    {
        $this->repository->renameIndexAlias(LoadIndexItems::ALIAS_TEMP, LoadIndexItems::ALIAS_REAL);
        $realAliasesLeft = $this->repository->findBy(['alias' => LoadIndexItems::ALIAS_REAL]);
        $this->assertCount(2, $realAliasesLeft);
    }
}
