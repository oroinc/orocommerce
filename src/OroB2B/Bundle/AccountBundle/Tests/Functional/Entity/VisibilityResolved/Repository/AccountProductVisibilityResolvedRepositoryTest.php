<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountProductRepository;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class AccountProductVisibilityResolvedRepositoryTest extends VisibilityResolvedRepositoryTestCase
{
    /**
     * {@inheritdoc}
     */
    public function insertByCategoryDataProvider()
    {
        return [
            'withoutWebsite' => [
                'websiteReference' => null,
                'accountReference' => 'account.level_1',
                'visibility' => BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
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
                'visibility' => BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
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

    public function clearTableDataProvider()
    {
        return ['expected_rows' => [1]];
    }

    /**
     * @inheritDoc
     */
    public function insertStaticDataProvider()
    {
        return ['expected_rows' => [3]];
    }

    /**
     * @return AccountProductVisibilityResolved[]
     */
    protected function getResolvedValues()
    {
        return $this->registry
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->findAll();
    }

    /**
     * @param AccountProductVisibilityResolved[] $visibilities
     * @param Product $product
     * @param Account $account
     * @param Website $website
     *
     * @return AccountProductVisibilityResolved|null
     */
    protected function getResolvedVisibility(
        $visibilities,
        Product $product,
        $account,
        Website $website
    ) {
        foreach ($visibilities as $visibility) {
            if ($visibility->getProduct()->getId() == $product->getId()
                && $visibility->getAccount()->getId() == $account->getId()
                && $visibility->getWebsite()->getId() == $website->getId()
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
                && $resolveVisibility->getAccount()->getId() == $visibility->getAccount()->getId()
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
            'OroB2BAccountBundle:Visibility\AccountProductVisibility'
        );
    }

    /**
     * @return AccountProductRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved'
        );
    }

    /**
     * @param AccountProductVisibilityResolved $visibilityResolved
     * @return AccountProductVisibilityResolved|null
     */
    public function findByPrimaryKey($visibilityResolved)
    {
        return $this->getRepository()->findByPrimaryKey(
            $visibilityResolved->getAccount(),
            $visibilityResolved->getProduct(),
            $visibilityResolved->getWebsite()
        );
    }

    public function testInsertForCurrentProductFallback()
    {
        /** @var AccountProductVisibility $accountProductVisibility */
        $accountProductVisibility = $this->getReference('product.5.visibility.account.level_1');
        $this->getRepository()->clearTable();
        $this->getRepository()->insertForCurrentProductFallback(
            $this->getInsertFromSelectExecutor()
        );
        $resolvedVisibility = $this->getResolvedVisibility(
            $this->getRepository()->findAll(),
            $accountProductVisibility->getProduct(),
            $accountProductVisibility->getAccount(),
            $accountProductVisibility->getWebsite()
        );
        $this->assertEquals(
            $resolvedVisibility->getVisibility(),
            AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL
        );
    }

    /**
     * @depends testInsertForCurrentProductFallback
     */
    public function testUpdateCurrentProductRelatedEntities()
    {
        $website = $this->getDefaultWebsite();
        /** @var Product $product */
        $product = $this->getReference('product.5');
        /** @var Account $account */
        $account = $this->getReference('account.level_1');

        $resolvedVisibility = $this->getRepository()->findByPrimaryKey($account, $product, $website);
        $this->assertNotNull($resolvedVisibility);
        $this->assertEquals(
            AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL,
            $resolvedVisibility->getVisibility()
        );

        $this->getRepository()
            ->updateCurrentProductRelatedEntities($website, $product, BaseProductVisibilityResolved::VISIBILITY_HIDDEN);

        $this->entityManager->refresh($resolvedVisibility);
        $this->assertEquals(
            AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL,
            $resolvedVisibility->getVisibility()
        );
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
