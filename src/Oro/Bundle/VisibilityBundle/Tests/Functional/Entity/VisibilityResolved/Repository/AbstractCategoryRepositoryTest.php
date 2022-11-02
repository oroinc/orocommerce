<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;

abstract class AbstractCategoryRepositoryTest extends WebTestCase
{
    use CatalogTrait;

    protected const ROOT_CATEGORY = 'root';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadCategoryVisibilityData::class]);
        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
    }

    abstract protected function getRepository(): EntityRepository;

    protected function getScopeManager(): ScopeManager
    {
        return $this->getContainer()->get('oro_scope.scope_manager');
    }

    protected function getMasterCatalog(): Category
    {
        return $this->getRootCategory();
    }

    protected function getDoctrine(): ManagerRegistry
    {
        return self::getContainer()->get('doctrine');
    }

    protected function getInsertExecutor(): InsertFromSelectQueryExecutor
    {
        return self::getContainer()->get('oro_entity.orm.insert_from_select_query_executor');
    }

    protected function getEntitiesCount(): int
    {
        return (int)$this->getRepository()->createQueryBuilder('entity')
            ->select('COUNT(entity.visibility)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
