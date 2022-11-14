<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\AbstractVisibilityRepository;

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
     * {@inheritDoc}
     */
    public function insertByCategoryDataProvider(): array
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
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $repository->deleteByProduct($product);
        $category = $this->getCategory($product);
        $repository->insertByProduct($this->getInsertFromSelectExecutor(), $product, $category);
        $visibilities = $repository->findBy(['product' => $product]);
        $this->assertCount(1, $visibilities);
    }

    /**
     * {@inheritDoc}
     */
    public function insertStaticDataProvider(): array
    {
        return ['expected_rows' => [9]];
    }

    public function clearTableDataProvider(): array
    {
        return ['expected_rows' => [8]];
    }

    /**
     * @return CustomerGroupProductVisibilityResolved[]
     */
    protected function getResolvedValues(): array
    {
        return $this->doctrine->getRepository(CustomerGroupProductVisibilityResolved::class)->findAll();
    }

    /**
     * {@inheritDoc}
     */
    protected function getResolvedVisibility(
        array $visibilities,
        Product $product,
        Scope $scope
    ): ?BaseProductVisibilityResolved {
        foreach ($visibilities as $visibility) {
            if ($visibility->getProduct()->getId() === $product->getId()
                && $visibility->getScope()->getId() === $scope->getId()
            ) {
                return $visibility;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    protected function getSourceVisibilityByResolved(
        ?array $sourceVisibilities,
        BaseProductVisibilityResolved $resolveVisibility
    ): ?VisibilityInterface {
        foreach ($sourceVisibilities as $visibility) {
            if ($resolveVisibility->getProduct()->getId() === $visibility->getProduct()->getId()
                && $resolveVisibility->getScope()->getId() === $visibility->getScope()->getId()
            ) {
                return $visibility;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    protected function getSourceRepository(): EntityRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(CustomerGroupProductVisibility::class);
    }

    /**
     * {@inheritDoc}
     */
    protected function getRepository(): AbstractVisibilityRepository
    {
        return $this->getContainer()->get('oro_visibility.customer_group_product_repository');
    }

    /**
     * {@inheritDoc}
     */
    public function findByPrimaryKey(
        BaseProductVisibilityResolved $visibilityResolved
    ): BaseProductVisibilityResolved {
        return $this->getRepository()->findByPrimaryKey(
            $visibilityResolved->getProduct(),
            $visibilityResolved->getScope()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getScope(string $targetEntityReference): Scope
    {
        return $this->scopeManager->find(
            CustomerGroupProductVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $this->getReference($targetEntityReference)]
        );
    }
}
