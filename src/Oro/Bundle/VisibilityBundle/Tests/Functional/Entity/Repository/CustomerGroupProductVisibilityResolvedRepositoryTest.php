<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerGroupProductRepository;

class CustomerGroupProductVisibilityResolvedRepositoryTest extends WebTestCase
{
    /**
     * @var CustomerGroupProductRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(['Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData']);
        $this->repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerGroupProductVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerGroupProductVisibilityResolved');
        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
    }

    public function testFindByPrimaryKey()
    {
        /** @var CustomerGroupProductVisibilityResolved $actualEntity */
        $actualEntity = $this->repository->findOneBy([]);
        if (!$actualEntity) {
            $this->markTestSkipped('Can\'t test method because fixture was not loaded.');
        }

        $expectedEntity = $this->repository->findByPrimaryKey(
            $actualEntity->getProduct(),
            $actualEntity->getScope()
        );

        $this->assertEquals(spl_object_hash($expectedEntity), spl_object_hash($actualEntity));
    }
    public function testDeleteByProduct()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->repository->deleteByProduct($product);
        $visibilities = $this->repository->findBy(['product' => $product]);
        $this->assertEmpty($visibilities, 'Deleting has failed');
    }

    public function testInsertByProduct()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_3);
        $this->repository->deleteByProduct($product);
        $insertExecutor = $this->getContainer()->get('oro_entity.orm.insert_from_select_query_executor');
        $this->repository->insertByProduct($insertExecutor, $product);
        $visibilities = $this->repository->findBy(['product' => $product]);
        $this->assertSame(2, count($visibilities));
    }

    /**
     * @return InsertFromSelectQueryExecutor
     */
    protected function getInsertFromSelectExecutor()
    {
        return $this->getContainer()
            ->get('oro_entity.orm.insert_from_select_query_executor');
    }
}
