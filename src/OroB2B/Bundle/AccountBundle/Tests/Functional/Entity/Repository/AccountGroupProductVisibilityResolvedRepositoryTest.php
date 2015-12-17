<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class AccountGroupProductVisibilityResolvedRepositoryTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var AccountGroupProductVisibilityResolvedRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(['OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData']);
        $this->entityManager = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');
        $this->repository = $this->entityManager
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');
    }

    public function testFindByPrimaryKey()
    {
        /** @var AccountGroupProductVisibilityResolved $actualEntity */
        $actualEntity = $this->repository->findOneBy([]);
        if (!$actualEntity) {
            $this->markTestSkipped('Can\'t test method because fixture was not loaded.');
        }

        $expectedEntity = $this->repository->findByPrimaryKey(
            $actualEntity->getAccountGroup(),
            $actualEntity->getProduct(),
            $actualEntity->getWebsite()
        );

        $this->assertEquals(spl_object_hash($expectedEntity), spl_object_hash($actualEntity));
    }

    public function testInsertUpdateDeleteAndHasEntity()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $accountGroup = $this->getReference(LoadGroups::GROUP1);

        $where = ['accountGroup' => $accountGroup, 'product' => $product, 'website' => $website];
        $this->assertFalse($this->repository->hasEntity($where));

        $insert = [
            'sourceProductVisibility' => null,
            'visibility' => BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            'source' => BaseProductVisibilityResolved::SOURCE_STATIC,
            'category' => null,
        ];
        $this->repository->insertEntity(array_merge($where, $insert));
        $this->assertTrue($this->repository->hasEntity($where));
        $this->assertEntityData(
            $where,
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            BaseProductVisibilityResolved::SOURCE_STATIC
        );

        $update = [
            'visibility' => BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
            'source' => BaseProductVisibilityResolved::SOURCE_CATEGORY,
        ];
        $this->repository->updateEntity($update, $where);
        $this->assertTrue($this->repository->hasEntity($where));
        $this->assertEntityData(
            $where,
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
            BaseProductVisibilityResolved::SOURCE_CATEGORY
        );

        $this->repository->deleteEntity($where);
        $this->assertFalse($this->repository->hasEntity($where));
    }

    /**
     * @param array $where
     * @param int $visibility
     * @param int $source
     */
    protected function assertEntityData(array $where, $visibility, $source)
    {
        $entity = $this->repository->findOneBy($where);

        $this->assertNotNull($entity);
        $this->entityManager->refresh($entity);

        $this->assertEquals($visibility, $entity->getVisibility());
        $this->assertEquals($source, $entity->getSource());
    }
}
