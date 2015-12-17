<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class AccountProductVisibilityResolvedRepositoryTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var AccountProductVisibilityResolvedRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(['OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData']);

        $this->entityManager = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved');
        $this->repository = $this->entityManager
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved');
    }

    public function testFindByPrimaryKey()
    {
        /** @var AccountProductVisibilityResolved $actualEntity */
        $actualEntity = $this->repository->findOneBy([]);
        if (!$actualEntity) {
            $this->markTestSkipped('Can\'t test method because fixture was not loaded.');
        }

        $expectedEntity = $this->repository->findByPrimaryKey(
            $actualEntity->getAccount(),
            $actualEntity->getProduct(),
            $actualEntity->getWebsite()
        );

        $this->assertEquals(spl_object_hash($expectedEntity), spl_object_hash($actualEntity));
    }

    public function testUpdateCurrentProductRelatedEntities()
    {
        $website = $this->getDefaultWebsite();
        /** @var Product $product */
        $product = $this->getReference('product.5');
        /** @var Account $account */
        $account = $this->getReference('account.level_1');

        $resolvedVisibility = $this->repository->findByPrimaryKey($account, $product, $website);
        $this->assertNotNull($resolvedVisibility);
        $this->assertEquals(BaseProductVisibilityResolved::VISIBILITY_VISIBLE, $resolvedVisibility->getVisibility());

        $this->repository
            ->updateCurrentProductRelatedEntities($website, $product, BaseProductVisibilityResolved::VISIBILITY_HIDDEN);

        $this->entityManager->refresh($resolvedVisibility);
        $this->assertEquals(BaseProductVisibilityResolved::VISIBILITY_HIDDEN, $resolvedVisibility->getVisibility());
    }

    public function testInsertUpdateDeleteAndHasEntity()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $account = $this->getReference(LoadAccounts::DEFAULT_ACCOUNT_NAME);

        $where = ['account' => $account, 'product' => $product, 'website' => $website];
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

    /**
     * @return Website
     */
    protected function getDefaultWebsite()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BWebsiteBundle:Website')
            ->getRepository('OroB2BWebsiteBundle:Website')->findOneBy(['name' => 'Default']);
    }
}
