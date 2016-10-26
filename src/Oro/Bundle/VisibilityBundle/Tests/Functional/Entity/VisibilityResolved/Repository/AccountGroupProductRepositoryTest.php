<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\AccountGroupProductRepository;

/**
 * @dbIsolation
 */
class AccountGroupProductRepositoryTest extends VisibilityResolvedRepositoryTestCase
{
    public function testInsertUpdateDeleteAndHasEntity()
    {
        $repository = $this->getRepository();

        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $accountGroup = $this->getReference(LoadGroups::GROUP1);
        $scope = $this->scopeManager->findOrCreate(
            AccountGroupProductVisibility::VISIBILITY_TYPE,
            ['accountGroup' => $accountGroup]
        );
        $where = ['product' => $product, 'scope' => $scope];
        $this->assertFalse($repository->hasEntity($where));
        $where = ['product' => $product, 'scope' => $scope];
        $this->assertInsert(
            $this->entityManager,
            $repository,
            $where,
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            BaseProductVisibilityResolved::SOURCE_STATIC,
            $scope->getId()
        );
        $this->assertUpdate(
            $this->entityManager,
            $repository,
            $where,
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
            BaseProductVisibilityResolved::SOURCE_CATEGORY
        );
        $this->assertDelete($repository, $where);
    }

    /**
     * {@inheritdoc}
     */
    public function insertByCategoryDataProvider()
    {
        return [
            [
                'accountGroupReference' => LoadGroups::GROUP1,
                'visibility' => BaseProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                'expectedData' => [
                    [
                        'product' => LoadProductData::PRODUCT_7,
                    ],
                    [
                        'product' => LoadProductData::PRODUCT_8,
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
        return ['expected_rows' => [6]];
    }

    /**
     * @return array
     */
    public function clearTableDataProvider()
    {
        return ['expected_rows' => [8]];
    }

    /**
     * @return AccountGroupProductVisibilityResolved[]
     */
    protected function getResolvedValues()
    {
        return $this->registry
            ->getRepository('OroVisibilityBundle:VisibilityResolved\AccountGroupProductVisibilityResolved')
            ->findAll();
    }

    /**
     * @param AccountGroupProductVisibilityResolved[] $visibilities
     * @param Product $product
     * @param Scope $scope
     *
     * @return AccountGroupProductVisibilityResolved|null
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
     * @param null|AccountGroupProductVisibility[] $sourceVisibilities
     * @param AccountGroupProductVisibilityResolved $resolveVisibility
     * @return null|AccountGroupProductVisibility
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
            'OroVisibilityBundle:Visibility\AccountGroupProductVisibility'
        );
    }

    /**
     * @return AccountGroupProductRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('oro_visibility.account_group_product_repository_holder')
            ->getRepository();
    }

    /**
     * @param AccountGroupProductVisibilityResolved $visibilityResolved
     * @return AccountGroupProductVisibilityResolved|null
     */
    public function findByPrimaryKey($visibilityResolved)
    {
        return $this->getRepository()->findByPrimaryKey(
            $visibilityResolved->getProduct(),
            $visibilityResolved->getScope()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getScope($targetEntityReference)
    {
        $targetEntity = $this->getReference($targetEntityReference);
        return $this->scopeManager->find(
            AccountGroupProductVisibility::VISIBILITY_TYPE,
            ['accountGroup' => $targetEntity]
        );
    }
}
