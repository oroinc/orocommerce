<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccounts;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\AccountProductRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class AccountProductRepositoryTest extends VisibilityResolvedRepositoryTestCase
{
    public function testInsertUpdateDeleteAndHasEntity()
    {
        $repository = $this->getRepository();

        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $account = $this->getReference(LoadAccounts::DEFAULT_ACCOUNT_NAME);
        $scope = $this->scopeManager->findOrCreate(
            'account_scope_visibility',
            ['account' => $account, 'website' => $website]
        );
        $where = ['product' => $product, 'scope' => $scope];
        $this->assertFalse($repository->hasEntity($where));

        $where['account'] = $account;

        $this->assertInsert(
            $this->entityManager,
            $repository,
            $where,
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
            BaseProductVisibilityResolved::SOURCE_CATEGORY,
            $scope
        );
        $this->assertUpdate(
            $this->entityManager,
            $repository,
            $where,
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            BaseProductVisibilityResolved::SOURCE_STATIC
        );
        $this->assertDelete($repository, $where);
    }

    public function testDeleteByProduct()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $account = $this->getReference(LoadAccounts::DEFAULT_ACCOUNT_NAME);
        $scope = $this->scopeManager->findOrCreate(
            'account_product_visibility',
            ['account' => $account, 'website' => $website]
        );
        $resolvedVisibility = new AccountProductVisibilityResolved($scope, $product);
        $this->entityManager->persist($resolvedVisibility);
        $this->entityManager->flush($resolvedVisibility);

        $repository = $this->getRepository();
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var $product Product */
        $visibilities = $repository->findBy(['product' => $product]);
        $this->assertNotEmpty($visibilities);

        $repository->deleteByProduct($product);
        $visibilities = $repository->findBy(['product' => $product]);
        $this->assertEmpty($visibilities, 'Deleting has failed');
    }

    public function testInsertByProduct()
    {
        $repository = $this->getRepository();
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var $product Product */
        $repository->deleteByProduct($product);
        $repository->insertByProduct($product, $this->getInsertFromSelectExecutor());
        $visibilities = $repository->findBy(['product' => $product]);
        $this->assertSame(1, count($visibilities));
    }

    /**
     * {@inheritdoc}
     */
    public function insertByCategoryDataProvider()
    {
        return [
            'withoutWebsite' => [
                'websiteReference' => null,
                'accountReference' => 'account.level_1',
                'visibility' => BaseProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                'expectedData' => [
                    [
                        'product' => LoadProductData::PRODUCT_8,
                        'website' => LoadWebsiteData::WEBSITE1,
                    ],
                ],
            ],
            'withWebsite1' => [
                'websiteReference' => LoadWebsiteData::WEBSITE1,
                'accountReference' => 'account.level_1',
                'visibility' => BaseProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                'expectedData' => [
                    [
                        'product' => LoadProductData::PRODUCT_8,
                        'website' => LoadWebsiteData::WEBSITE1,
                    ],
                ],
            ],
            'withWebsite2' => [
                'websiteReference' => LoadWebsiteData::WEBSITE2,
                'accountReference' => 'account.level_1',
                'visibility' => BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
                'expectedData' => [],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function insertStaticDataProvider()
    {
        return ['expected_rows' => [4]];
    }
    /**
     * @return AccountProductVisibilityResolved[]
     */
    protected function getResolvedValues()
    {
        return $this->registry
            ->getRepository('OroVisibilityBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->findAll();
    }
    /**
     * @param AccountProductVisibilityResolved[] $visibilities
     * @param Product $product
     * @param Account $account
     * @param Scope $scope
     *
     * @return AccountProductVisibilityResolved|null
     */
    protected function getResolvedVisibility(
        $visibilities,
        Product $product,
        $account,
        Scope $scope
    ) {
        foreach ($visibilities as $visibility) {
            if ($visibility->getProduct()->getId() == $product->getId()
                && $visibility->getAccount()->getId() == $account->getId()
                && $visibility->getScope()->getId() == $scope->getId()
            ) {
                return $visibility;
            }
        }
        return null;
    }
    /**
     * @param null|AccountProductVisibility[] $sourceVisibilities
     * @param AccountProductVisibilityResolved $resolveVisibility
     * @return null|AccountProductVisibility
     */
    protected function getSourceVisibilityByResolved($sourceVisibilities, $resolveVisibility)
    {
        foreach ($sourceVisibilities as $visibility) {
            if ($resolveVisibility->getProduct()->getId() == $visibility->getProduct()->getId()
                && $resolveVisibility->getScope()->getId() == $visibility->getScope()->getId()
            ) {
                return $visibility;
            }
        }
        return null;
    }
    /**
     * @return EntityRepository
     */
    protected function getSourceRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroVisibilityBundle:Visibility\AccountProductVisibility'
        );
    }
    /**
     * @return AccountProductRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroVisibilityBundle:VisibilityResolved\AccountProductVisibilityResolved'
        );
    }
    /**
     * @param AccountProductVisibilityResolved $visibilityResolved
     * @return AccountProductVisibilityResolved|null
     */
    public function findByPrimaryKey($visibilityResolved)
    {
        return $this->getRepository()->findByPrimaryKey(
            $visibilityResolved->getProduct(),
            $visibilityResolved->getScope()
        );
    }

    /**
     * @return Website
     */
    protected function getDefaultWebsite()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroWebsiteBundle:Website')
            ->getRepository('OroWebsiteBundle:Website')->findOneBy(['name' => 'Default']);
    }
}
