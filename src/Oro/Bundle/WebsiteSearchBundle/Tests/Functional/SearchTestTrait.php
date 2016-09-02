<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;

trait SearchTestTrait
{
    /**
     * Workaround to clear MyISAM table as it's not rolled back by transaction.
     */
    public function truncateIndexTextTable()
    {
        /** @var OroEntityManager $manager */
        $manager = $this->getDoctrine()->getManager('search');

        if ($manager->getConnection()->getDatabasePlatform()->getName() === DatabasePlatformInterface::DATABASE_MYSQL) {
            /** @var EntityRepository $repository */
            $repository = $this->getDoctrine()->getRepository(IndexText::class, 'search');

            $repository->createQueryBuilder('t')
                ->delete()
                ->getQuery()
                ->execute();
        }
    }

    /**
     * @return WebsiteSearchIndexRepository
     */
    private function getItemRepository()
    {
        return $this->getDoctrine()->getRepository(Item::class, 'search');
    }

    /**
     * @return \Oro\Bundle\EntityBundle\ORM\Registry
     */
    private function getDoctrine()
    {
        /** @var ContainerAwareTrait $this */
        return $this->getContainer()->get('doctrine');
    }

    /**
     * @param string $entity
     * @return EntityRepository
     */
    private function getRepository($entity)
    {
        return $this->getDoctrine()->getRepository($entity, 'search');
    }

    /**
     * @param int $count
     * @param string $entityClass
     */
    private function assertEntityCount($count, $entityClass)
    {
        /** @var WebTestCase $this */
        $repository = $this->getRepository($entityClass);

        $actualCount = $repository->createQueryBuilder('t')
            ->select('count(t)')
            ->getQuery()
            ->getSingleScalarResult();

        $this->assertEquals($count, $actualCount);
    }
}
