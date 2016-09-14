<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\WebsiteBundle\Entity\Website;
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
        /** @var OroEntityManager $manager */
        $manager = $this->getDoctrine()->getManager('search');

        if ($manager->getConnection()->getDatabasePlatform()->getName() === DatabasePlatformInterface::DATABASE_MYSQL) {
            $repository = $manager->getRepository(IndexText::class);

            $repository->createQueryBuilder('t')
                ->delete()
                ->getQuery()
                ->execute();
        }
    }

    /** @return ContainerInterface */
    abstract protected function getContainer();

    /**
     * @return int
     */
    public function getDefaultWebsiteId()
    {
        return $this
            ->getDoctrine()
            ->getRepository(Website::class)
            ->getDefaultWebsite()
            ->getId();
    }

    /**
     * @return WebsiteSearchIndexRepository
     */
    private function getItemRepository()
    {
        return $this->getDoctrine()->getRepository(Item::class, 'search');
    }

    /**
     * @return Registry
     */
    private function getDoctrine()
    {
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
        $repository = $this->getRepository($entityClass);

        $actualCount = $repository->createQueryBuilder('t')
            ->select('count(t)')
            ->getQuery()
            ->getSingleScalarResult();

        $this->assertEquals($count, $actualCount);
    }
}
