<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerProductRepository;

class CustomerProductRepositoryTest extends VisibilityResolvedRepositoryTestCase
{
    public function testInsertUpdateDeleteAndHasEntity()
    {
        $repository = $this->getRepository();

        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);
        $scope = $this->scopeManager->findOrCreate(
            CustomerProductVisibility::VISIBILITY_TYPE,
            ['customer' => $customer]
        );
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
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);
        $scope = $this->scopeManager->findOrCreate('customer_product_visibility', ['customer' => $customer]);
        $resolvedVisibility = new CustomerProductVisibilityResolved($scope, $product);
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
        $category = $this->getCategory($product);
        $repository->insertByProduct($this->getInsertFromSelectExecutor(), $product, $category);
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
                'customerReference' => 'customer.level_1',
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
        return ['expected_rows' => [5]];
    }
    /**
     * @return CustomerProductVisibilityResolved[]
     */
    protected function getResolvedValues()
    {
        return $this->registry
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerProductVisibilityResolved')
            ->findAll();
    }
    /**
     * @param CustomerProductVisibilityResolved[] $visibilities
     * @param Product $product
     * @param Scope $scope
     *
     * @return CustomerProductVisibilityResolved|null
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
     * @param null|CustomerProductVisibility[] $sourceVisibilities
     * @param CustomerProductVisibilityResolved $resolveVisibility
     * @return null|CustomerProductVisibility
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
            'OroVisibilityBundle:Visibility\CustomerProductVisibility'
        );
    }
    /**
     * @return CustomerProductRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()
            ->get('oro_visibility.customer_product_repository');
    }
    /**
     * @param CustomerProductVisibilityResolved $visibilityResolved
     * @return CustomerProductVisibilityResolved|null
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
            CustomerProductVisibility::VISIBILITY_TYPE,
            ['customer' => $targetEntity]
        );
    }
}
