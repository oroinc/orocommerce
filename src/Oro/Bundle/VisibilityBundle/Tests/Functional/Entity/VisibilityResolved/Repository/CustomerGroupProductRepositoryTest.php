<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerGroupProductRepository;

class CustomerGroupProductRepositoryTest extends VisibilityResolvedRepositoryTestCase
{
    public function testInsertUpdateDeleteAndHasEntity()
    {
        $repository = $this->getRepository();

        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $customerGroup = $this->getReference(LoadGroups::GROUP1);
        $scope = $this->scopeManager->findOrCreate(
            CustomerGroupProductVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroup]
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
                'customerGroupReference' => LoadGroups::GROUP1,
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

    public function testInsertByProduct()
    {
        $repository = $this->getRepository();
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var $product Product */
        $repository->deleteByProduct($product);
        $category = $this->getCategory($product);
        $repository->insertByProduct($this->getInsertFromSelectExecutor(), $product, $category);
        $visibilities = $repository->findBy(['product' => $product]);
        $this->assertSame(1, count($visibilities));
    }

    /**
     * @inheritDoc
     */
    public function insertStaticDataProvider()
    {
        return ['expected_rows' => [9]];
    }

    /**
     * @return array
     */
    public function clearTableDataProvider()
    {
        return ['expected_rows' => [8]];
    }

    /**
     * @return CustomerGroupProductVisibilityResolved[]
     */
    protected function getResolvedValues()
    {
        return $this->registry
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerGroupProductVisibilityResolved')
            ->findAll();
    }

    /**
     * @param CustomerGroupProductVisibilityResolved[] $visibilities
     * @param Product $product
     * @param Scope $scope
     *
     * @return CustomerGroupProductVisibilityResolved|null
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
     * @param null|CustomerGroupProductVisibility[] $sourceVisibilities
     * @param CustomerGroupProductVisibilityResolved $resolveVisibility
     * @return null|CustomerGroupProductVisibility
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
            'OroVisibilityBundle:Visibility\CustomerGroupProductVisibility'
        );
    }

    /**
     * @return CustomerGroupProductRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('oro_visibility.customer_group_product_repository');
    }

    /**
     * @param CustomerGroupProductVisibilityResolved $visibilityResolved
     * @return CustomerGroupProductVisibilityResolved|null
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
            CustomerGroupProductVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $targetEntity]
        );
    }
}
