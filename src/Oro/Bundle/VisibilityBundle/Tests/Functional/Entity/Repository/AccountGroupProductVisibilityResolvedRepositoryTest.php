<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\AccountGroupProductRepository;

/**
 * @dbIsolation
 */
class AccountGroupProductVisibilityResolvedRepositoryTest extends WebTestCase
{
    /**
     * @var AccountGroupProductRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(['Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData']);
        $this->repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountGroupProductVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');
        $this->getContainer()->get('oro_account.visibility.cache.cache_builder')->buildCache();
    }

    public function testFindByPrimaryKey()
    {
        /** @var AccountGroupProductVisibilityResolved $actualEntity */
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
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var $product Product */
        $this->repository->deleteByProduct($product);
        $visibilities = $this->repository->findBy(['product' => $product]);
        $this->assertEmpty($visibilities, 'Deleting has failed');
    }

    public function testInsertByProduct()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_3);
        /** @var $product Product */
        $this->repository->deleteByProduct($product);
        $this->repository->insertByProduct($product, $this->getInsertFromSelectExecutor());
        $visibilities = $this->repository->findBy(['product' => $product]);
        $this->assertSame(1, count($visibilities));
    }

    /**
     * @return \Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor
     */
    protected function getInsertFromSelectExecutor()
    {
        return $this->getContainer()
            ->get('oro_entity.orm.insert_from_select_query_executor');
    }
}
