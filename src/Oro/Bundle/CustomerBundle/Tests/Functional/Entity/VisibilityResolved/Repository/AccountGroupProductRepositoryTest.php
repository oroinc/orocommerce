<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\Repository\AccountGroupProductRepository;
use Oro\Bundle\CustomerBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\WebsiteBundle\Entity\Website;
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
        $where = ['accountGroup' => $accountGroup, 'product' => $product, 'website' => $website];
        $this->assertFalse($repository->hasEntity($where));
        $this->assertInsert(
            $this->entityManager,
            $repository,
            $where,
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            BaseProductVisibilityResolved::SOURCE_STATIC
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
        return ['expected_rows' => [8]];
    }

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
            ->getRepository('OroCustomerBundle:VisibilityResolved\AccountGroupProductVisibilityResolved')
            ->findAll();
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
        $accountGroup,
        Website $website
    ) {
        foreach ($visibilities as $visibility) {
            if ($visibility->getProduct()->getId() == $product->getId()
                && $visibility->getAccountGroup()->getId() == $accountGroup->getId()
                && $visibility->getWebsite()->getId() == $website->getId()
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
                && $resolveVisibility->getWebsite()->getId() == $visibility->getWebsite()->getId()
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
            'OroCustomerBundle:Visibility\AccountGroupProductVisibility'
        );
    }

    /**
     * @return AccountGroupProductRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroCustomerBundle:VisibilityResolved\AccountGroupProductVisibilityResolved'
        );
    }

    /**
     * @param AccountGroupProductVisibilityResolved $visibilityResolved
     * @return AccountGroupProductVisibilityResolved|null
     */
    public function findByPrimaryKey($visibilityResolved)
    {
        return $this->getRepository()->findByPrimaryKey(
            $visibilityResolved->getAccountGroup(),
            $visibilityResolved->getProduct(),
            $visibilityResolved->getWebsite()
        );
    }
}
