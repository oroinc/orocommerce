<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\AbstractVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerGroupProductRepository;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\ResolvedEntityRepositoryTestTrait;

abstract class VisibilityResolvedRepositoryTestCase extends WebTestCase
{
    use ResolvedEntityRepositoryTestTrait;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var EntityManager */
    protected $entityManager;

    /** @var ScopeManager */
    protected $scopeManager;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->doctrine = $this->getContainer()->get('doctrine');
        $this->entityManager = $this->doctrine->getManager();
        $this->scopeManager = $this->getContainer()->get('oro_scope.scope_manager');

        $this->loadFixtures($this->getFixtures());
    }

    protected function getFixtures(): array
    {
        return [LoadProductVisibilityData::class];
    }

    protected function tearDown(): void
    {
        $this->doctrine->getManager()->clear();
        parent::tearDown();
    }

    public function testClearTable()
    {
        $countQuery = $this->getRepository()
            ->createQueryBuilder('entity')
            ->select('COUNT(entity.visibility)')
            ->getQuery();

        $this->getRepository()->clearTable();

        $this->assertEquals(0, $countQuery->getSingleScalarResult());
    }

    /**
     * @dataProvider insertByCategoryDataProvider
     */
    public function testInsertByCategory(string $targetEntityReference, int $visibility, array $expectedData)
    {
        $this->getRepository()->clearTable();
        $scope = $this->getScope($targetEntityReference);
        /** @var CustomerGroupProductRepository $repository */
        $repository = $this->getRepository();
        $repository->insertByCategory($this->getInsertFromSelectExecutor(), $this->scopeManager, $scope);
        $resolvedEntities = $this->getResolvedValues();
        $this->assertCount(count($expectedData), $resolvedEntities);
        foreach ($expectedData as $data) {
            /** @var Product $product */
            $product = $this->getReference($data['product']);

            $resolvedVisibility = $this->getResolvedVisibility($resolvedEntities, $product, $scope);
            $this->assertEquals($this->getCategory($product)->getId(), $resolvedVisibility->getCategory()->getId());
            $this->assertEquals($visibility, $resolvedVisibility->getVisibility());
        }
    }

    abstract public function getScope(string $targetEntityReference): Scope;

    /**
     * @dataProvider insertStaticDataProvider
     */
    public function testInsertStatic(int $expectedRows)
    {
        /** @var CustomerGroupProductRepository $repository */
        $repository = $this->getRepository();
        $repository->clearTable();
        $repository->insertStatic($this->getInsertFromSelectExecutor());
        $resolved = $this->getResolvedValues();
        $this->assertCount($expectedRows, $resolved);
        $visibilities = $this->getSourceRepository()->findAll();
        foreach ($resolved as $val) {
            $source = $this->getSourceVisibilityByResolved($visibilities, $val);
            $this->assertNotNull($source);
            if ($val->getVisibility() === BaseProductVisibilityResolved::VISIBILITY_HIDDEN) {
                $visibility = VisibilityInterface::HIDDEN;
            } elseif ($val->getVisibility() === CustomerProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL) {
                $visibility = CustomerProductVisibility::CURRENT_PRODUCT;
            } else {
                $visibility = VisibilityInterface::VISIBLE;
            }
            $this->assertEquals(
                $source->getVisibility(),
                $visibility
            );
        }
    }

    public function testFindByPrimaryKey()
    {
        /** @var BaseProductVisibilityResolved $actualEntity */
        $actualEntity = $this->getRepository()->findOneBy([]);
        if (!$actualEntity) {
            $this->markTestSkipped('Can\'t test method because fixture was not loaded.');
        }

        $this->assertEquals(spl_object_hash($this->findByPrimaryKey($actualEntity)), spl_object_hash($actualEntity));
    }

    protected function getCategory(Product $product): ?Category
    {
        return $this->doctrine->getRepository(Category::class)->findOneByProduct($product);
    }

    protected function getInsertFromSelectExecutor(): InsertFromSelectQueryExecutor
    {
        return $this->getContainer()->get('oro_entity.orm.insert_from_select_query_executor');
    }

    abstract public function findByPrimaryKey(
        BaseProductVisibilityResolved $visibilityResolved
    ): BaseProductVisibilityResolved;

    abstract public function insertByCategoryDataProvider(): array;

    abstract public function insertStaticDataProvider(): array;

    abstract protected function getRepository(): AbstractVisibilityRepository;

    abstract protected function getSourceRepository(): EntityRepository;

    /**
     * @return BaseProductVisibilityResolved[]
     */
    abstract protected function getResolvedValues(): array;

    /**
     * @param BaseProductVisibilityResolved[] $visibilities
     * @param Product $product
     * @param Scope $scope
     *
     * @return BaseProductVisibilityResolved|null
     */
    abstract protected function getResolvedVisibility(
        array $visibilities,
        Product $product,
        Scope $scope
    ): ?BaseProductVisibilityResolved;

    /**
     * @param VisibilityInterface[]|null $sourceVisibilities
     * @param BaseProductVisibilityResolved $resolveVisibility
     *
     * @return VisibilityInterface|null
     */
    abstract protected function getSourceVisibilityByResolved(
        ?array $sourceVisibilities,
        BaseProductVisibilityResolved $resolveVisibility
    ): ?VisibilityInterface;
}
