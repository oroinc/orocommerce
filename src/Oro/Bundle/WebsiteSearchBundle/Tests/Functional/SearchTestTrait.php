<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository;

trait SearchTestTrait
{
    /**
     * Workaround to clear MyISAM table as it's not rolled back by transaction.
     */
    public function truncateIndexTextTable()
    {
        /** @var EntityRepository $repository */
        /** @var WebTestCase $this */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(IndexText::class)
            ->getRepository(IndexText::class);

        $repository->createQueryBuilder('t')
            ->delete()
            ->getQuery()
            ->execute();
    }

    /**
     * @return WebsiteSearchIndexRepository
     */
    private function getItemRepository()
    {
        /** @var WebTestCase $this */
        return $this->getContainer()->get('doctrine')->getRepository(Item::class, 'search');
    }

    /**
     * @param string $entity
     * @return EntityRepository
     */
    private function getRepository($entity)
    {
        /** @var WebTestCase $this */
        return $this->getContainer()
            ->get('doctrine.orm.search_entity_manager')
            ->getRepository($entity);
    }

    /**
     * @param int $count
     * @param string $entityClass
     */
    private function assertEntityCount($count, $entityClass)
    {
        $repository = $this->getRepository($entityClass);

        $actualCount = $repository->createQueryBuilder('t')
            ->select('count(t)')
            ->getQuery()
            ->getSingleScalarResult();

        $this->assertEquals($count, $actualCount);
    }
}
