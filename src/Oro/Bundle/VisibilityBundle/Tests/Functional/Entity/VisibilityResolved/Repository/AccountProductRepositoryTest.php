<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccounts;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\AccountProductRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class AccountProductRepositoryTest extends VisibilityResolvedRepositoryTestCase
{
    public function testInsertUpdateDeleteAndHasEntity()
    {
        $repository = $this->getRepository();

        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $account = $this->getReference(LoadAccounts::DEFAULT_ACCOUNT_NAME);
        $scope = $this->scopeManager->findOrCreate(AccountProductVisibility::VISIBILITY_TYPE, ['account' => $account]);
        $where = ['product' => $product, 'scope' => $scope];
        $this->assertFalse($repository->hasEntity($where));

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
        $account = $this->getReference(LoadAccounts::DEFAULT_ACCOUNT_NAME);
        $scope = $this->scopeManager->findOrCreate('account_product_visibility', ['account' => $account]);
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
        $repository->insertByProduct($product);
        $visibilities = $repository->findBy(['product' => $product]);
        $this->assertSame(1, count($visibilities));
    }

    /**
     * {@inheritdoc}
     */
    public function insertByCategoryDataProvider()
    {
        return [
            [
                'accountReference' => 'account.level_1',
                'visibility' => BaseProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                'expectedData' => [
                    [
                        'product' => LoadProductData::PRODUCT_8
                    ],
                ],
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
     * @param Scope $scope
     *
     * @return AccountProductVisibilityResolved|null
     */
    protected function getResolvedVisibility(
        $visibilities,
        Product $product,
        Scope $scope
    ) {
        foreach ($visibilities as $visibility) {
            if ($visibility->getProduct()->getId() == $product->getId()
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
        return $this->getContainer()
            ->get('oro_visibility.account_product_repository_holder')
            ->getRepository();
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
