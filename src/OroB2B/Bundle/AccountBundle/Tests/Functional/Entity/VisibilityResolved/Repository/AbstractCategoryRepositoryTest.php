<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;

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

        $this->loadFixtures([
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData'
        ]);

        $this->repository = $this->getRepository();
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
        return $this->getManagerRegistry()->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
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
