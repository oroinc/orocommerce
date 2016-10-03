<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractCategoryRepositoryTest extends WebTestCase
{
    const ROOT_CATEGORY = 'root';

    /**
     * @var EntityRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            'Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData'
        ]);

        $this->repository = $this->getRepository();
        $this->getContainer()->get('oro_account.visibility.cache.cache_builder')->buildCache();
    }

    /**
     * @return EntityRepository
     */
    abstract protected function getRepository();

    /**
     * @return Category
     */
    protected function getMasterCatalog()
    {
        return $this->getManagerRegistry()->getManagerForClass('OroCatalogBundle:Category')
            ->getRepository('OroCatalogBundle:Category')
            ->getMasterCatalogRoot();
    }

    /**
     * @return ManagerRegistry
     */
    protected function getManagerRegistry()
    {
        return $this->getContainer()->get('doctrine');
    }

    /**
     * @return InsertFromSelectQueryExecutor
     */
    protected function getInsertExecutor()
    {
        return $this->getContainer()->get('oro_entity.orm.insert_from_select_query_executor');
    }

    /**
     * @return int
     */
    protected function getEntitiesCount()
    {
        return (int)$this->repository->createQueryBuilder('entity')
            ->select('COUNT(entity.visibility)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
