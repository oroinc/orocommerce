<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\AccountGroupProductRepository;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class AccountGroupProductRepositoryTest extends VisibilityResolvedRepositoryTestCase
{
    public function testInsertUpdateDeleteAndHasEntity()
    {
        $repository = $this->getRepository();

        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $accountGroup = $this->getReference(LoadGroups::GROUP1);
        $scope = $this->scopeManager->findOrCreate(
            'account_group_product_visibility',
            ['accountGroup' => $accountGroup, 'website' => $website]
        );
        $where = ['product' => $product, 'scope' => $scope];
        $this->assertFalse($repository->hasEntity($where));
        $where = ['accountGroup' => $accountGroup, 'product' => $product, 'scope' => $scope];
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
            'withoutWebsite' => [
                'websiteReference' => null,
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
            'withWebsite1' => [
                'websiteReference' => LoadWebsiteData::WEBSITE1,
                'accountGroupReference' => LoadGroups::GROUP1,
                'visibility' => BaseProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
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
     * @param AccountGroup $accountGroup
     * @param Scope $scope
     *
     * @return AccountGroupProductVisibilityResolved|null
     */
    protected function getResolvedVisibility(
        $visibilities,
        Product $product,
        $accountGroup,
        Scope $scope
    ) {
        foreach ($visibilities as $visibility) {
            if ($visibility->getProduct()->getId() == $product->getId()
                && $visibility->getAccountGroup()->getId() == $accountGroup->getId()
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
                && $resolveVisibility->getAccountGroup()->getId() == $visibility->getAccountGroup()->getId()
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
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroVisibilityBundle:VisibilityResolved\AccountGroupProductVisibilityResolved'
        );
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
}
