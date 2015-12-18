<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountGroupProductRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository\ResolvedEntityRepositoryTestTrait;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class AccountGroupProductVisibilityResolvedRepositoryTest extends WebTestCase
{
    use ResolvedEntityRepositoryTestTrait;

    /** @var  Registry */
    protected $registry;

    /** @var AccountGroupProductRepository */
    protected $repository;

    /** @var  EntityManager */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient();
        $this->registry = $this->getContainer()->get('doctrine');
        $this->entityManager = $this->registry
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');

        $this->repository = $this->entityManager
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
                'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
                'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
            ]
        );
    }

    protected function tearDown()
    {
        $this->registry->getManager()->clear();
        parent::tearDown();
    }

    public function testClearTable()
    {
        $expectedCount = 8;
        $this->assertCount($expectedCount, $this->repository->findAll());
        $deletedCount = $this->repository->clearTable();

        $this->assertCount(0, $this->repository->findAll());
        $this->assertEquals($expectedCount, $deletedCount);
    }

    /**
     * @dataProvider insertByCategoryDataProvider
     *
     * @param string $websiteReference
     * @param string $accountGroupReference
     * @param string $visibility
     * @param array $expectedData
     */
    public function testInsertByCategory($websiteReference, $accountGroupReference, $visibility, array $expectedData)
    {
        /** @var AccountGroup $group */
        $group = $this->getReference($accountGroupReference);
        $this->repository->clearTable();
        $website = $websiteReference ? $this->getReference($websiteReference) : null;
        $this->repository->insertByCategory(
            $this->getInsertFromSelectExecutor(),
            $visibility,
            $this->registry->getRepository('OroB2BCatalogBundle:Category')->findAll(),
            $group->getId(),
            $website
        );
        $resolvedEntities = $this->getResolvedValues();
        $this->assertCount(count($expectedData), $resolvedEntities);
        foreach ($expectedData as $data) {
            /** @var Product $product */
            $product = $this->getReference($data['product']);
            /** @var Website $website */
            $website = $this->getReference($data['website']);
            $resolvedVisibility = $this->getResolvedVisibility($resolvedEntities, $product, $group, $website);
            $this->assertEquals($this->getCategory($product)->getId(), $resolvedVisibility->getCategory()->getId());
            $this->assertEquals($resolvedVisibility->getVisibility(), $visibility);
        }
    }

    /**
     * @return array
     */
    public function insertByCategoryDataProvider()
    {
        return [
            'withoutWebsite' => [
                'websiteReference' => null,
                'accountGroupReference' => LoadGroups::GROUP1,
                'visibility' => BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
                'expectedData' => [
                    [
                        'product' => LoadProductData::PRODUCT_7,
                        'website' => LoadWebsiteData::WEBSITE1,
                    ],
                    [
                        'product' => LoadProductData::PRODUCT_8,
                        'website' => LoadWebsiteData::WEBSITE1,
                    ],
                ],
            ],
            'withWebsite1' => [
                'websiteReference' => LoadWebsiteData::WEBSITE1,
                'accountGroupReference' => LoadGroups::GROUP1,
                'visibility' => BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
                'expectedData' => [
                    [
                        'product' => LoadProductData::PRODUCT_7,
                        'website' => LoadWebsiteData::WEBSITE1,
                    ],
                    [
                        'product' => LoadProductData::PRODUCT_8,
                        'website' => LoadWebsiteData::WEBSITE1,
                    ],
                ],
            ],
            'withWebsite2' => [
                'websiteReference' => LoadWebsiteData::WEBSITE2,
                'accountGroupReference' => LoadGroups::GROUP1,
                'visibility' => BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
                'expectedData' => [],
            ],
        ];
    }

    public function testInsertStatic()
    {
        $this->repository->clearTable();
        $this->repository->insertStatic($this->getInsertFromSelectExecutor());
        $resolved = $this->getResolvedValues();
        $this->assertCount(6, $resolved);
        $visibilities = $this->registry
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
            ->findAll();
        foreach ($resolved as $resolvedValue) {
            $source = $this->getVisibility(
                $visibilities,
                $resolvedValue->getProduct(),
                $resolvedValue->getAccountGroup(),
                $resolvedValue->getWebsite()
            );
            $this->assertNotNull($source);
            if ($resolvedValue->getVisibility() == BaseProductVisibilityResolved::VISIBILITY_HIDDEN) {
                $visibility = AccountGroupProductVisibility::HIDDEN;
            } else {
                $visibility = AccountGroupProductVisibility::VISIBLE;
            }
            $this->assertEquals(
                $source->getVisibility(),
                $visibility
            );
        }
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

        $this->assertInsert(
            $this->entityManager,
            $this->repository,
            $where,
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            BaseProductVisibilityResolved::SOURCE_STATIC
        );
        $this->assertUpdate(
            $this->entityManager,
            $this->repository,
            $where,
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
            BaseProductVisibilityResolved::SOURCE_CATEGORY
        );
        $this->assertDelete($this->repository, $where);
    }

    /**
     * @param Product $product
     * @return null|\OroB2B\Bundle\CatalogBundle\Entity\Category
     */
    protected function getCategory(Product $product)
    {
        return $this->registry
            ->getRepository('OroB2BCatalogBundle:Category')
            ->findOneByProduct($product);
    }

    /**
     * @return AccountGroupProductVisibilityResolved[]
     */
    protected function getResolvedValues()
    {
        return $this->repository->findAll();
    }

    /**
     * @param AccountGroupProductVisibilityResolved[] $visibilities
     * @param Product $product
     * @param AccountGroup $accountGroup
     * @param Website $website
     *
     * @return AccountGroupProductVisibilityResolved|null
     */
    protected function getResolvedVisibility(
        $visibilities,
        Product $product,
        AccountGroup $accountGroup,
        Website $website
    ) {
        /** @var AccountGroupProductVisibility[] $visibilities */
        return $this->getVisibility($visibilities, $product, $accountGroup, $website);
    }

    /**
     * @param AccountGroupProductVisibility[] $visibilities
     * @param Product $product
     * @param AccountGroup $accountGroup
     * @param Website $website
     *
     * @return AccountGroupProductVisibility|null
     */
    protected function getVisibility(
        $visibilities,
        Product $product,
        AccountGroup $accountGroup,
        Website $website
    ) {
        foreach ($visibilities as $visibility) {
            if (spl_object_hash($product) == spl_object_hash($visibility->getProduct())
                && spl_object_hash($accountGroup) == spl_object_hash($visibility->getAccountGroup())
                && spl_object_hash($website) == spl_object_hash($visibility->getWebsite())
            ) {
                return $visibility;
            }
        }

        return null;
    }

    /**
     * @return InsertFromSelectQueryExecutor
     */
    protected function getInsertFromSelectExecutor()
    {
        return $this->getContainer()->get('oro_entity.orm.insert_from_select_query_executor');
    }

    /**
     * @return EntityRepository
     */
    protected function getSourceRepository()
    {
        return $this->registry->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility');
    }
}
