<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\OrganizationBundle\Tests\Functional\OrganizationTrait;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractCategoryRepositoryTest extends WebTestCase
{
    use OrganizationTrait, CatalogTrait;

    const ROOT_CATEGORY = 'root';

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->scopeManager = $this->getContainer()->get('oro_scope.scope_manager');
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            'Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData'
        ]);

        $this->repository = $this->getRepository();
        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
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
        return $this->getRootCategory();
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
