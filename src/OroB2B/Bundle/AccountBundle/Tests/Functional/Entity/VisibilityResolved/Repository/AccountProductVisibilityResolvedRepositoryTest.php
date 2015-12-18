<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts;
use OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository\ResolvedEntityRepositoryTestTrait;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class AccountProductVisibilityResolvedRepositoryTest extends WebTestCase
{
    use ResolvedEntityRepositoryTestTrait;

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

    public function testInsertUpdateDeleteAndHasEntity()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $account = $this->getReference(LoadAccounts::DEFAULT_ACCOUNT_NAME);

        $where = ['account' => $account, 'product' => $product, 'website' => $website];
        $this->assertFalse($this->repository->hasEntity($where));

        $this->assertInsert(
            $this->entityManager,
            $this->repository,
            $where,
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
            BaseProductVisibilityResolved::SOURCE_CATEGORY
        );
        $this->assertUpdate(
            $this->entityManager,
            $this->repository,
            $where,
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            BaseProductVisibilityResolved::SOURCE_STATIC
        );
        $this->assertDelete($this->repository, $where);
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
