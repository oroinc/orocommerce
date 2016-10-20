<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadProductVisibilityFallbackCategoryData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @var array
     */
    protected $products = [
        LoadProductData::PRODUCT_1,
        LoadProductData::PRODUCT_2,
        LoadProductData::PRODUCT_3,
        LoadProductData::PRODUCT_4,
        LoadProductData::PRODUCT_5,
        LoadProductData::PRODUCT_6,
        LoadProductData::PRODUCT_7,
        LoadProductData::PRODUCT_8,
    ];

    /**
     * @var array
     */
    protected $accountGroups = [
        LoadGroups::GROUP2,
        LoadGroups::GROUP3,
    ];

    /**
     * @var array
     */
    protected $accounts = [
        'account.level_1.1',
        'account.level_1.2',
        'account.level_1.2.1',
        'account.level_1.2.1.1',
        'account.level_1.3.1',
        'account.level_1.3.1.1',
        'account.level_1.4',
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCategoryVisibilityData::class,
            LoadCategoryProductData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->em = $manager;
        $this->scopeManager = $this->container->get('oro_scope.scope_manager');

        foreach ($this->products as $productReference) {
            /** @var Product $product */
            $product = $this->getReference($productReference);
            $this->createProductVisibility($product);
            foreach ($this->accountGroups as $accountGroupReference) {
                /** @var AccountGroup $accountGroup */
                $accountGroup = $this->getReference($accountGroupReference);
                $this->createAccountGroupProductVisibilityResolved($accountGroup, $product);
            }
            foreach ($this->accounts as $accountReference) {
                /** @var Account $account */
                $account = $this->getReference($accountReference);
                $this->createAccountProductVisibilityResolved($account, $product);
            }
        }

        $this->em->flush();
    }


    /**
     * @param Product $product
     */
    protected function createProductVisibility(Product $product)
    {
        $scope = $this->scopeManager->findOrCreate('product_visibility');
        $productVisibility = (new ProductVisibility())
            ->setProduct($product)
            ->setScope($scope)
            ->setVisibility(ProductVisibility::CATEGORY);

        $this->em->persist($productVisibility);
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Product $product
     */
    protected function createAccountGroupProductVisibilityResolved(AccountGroup $accountGroup, Product $product)
    {
        $scope = $this->scopeManager->findOrCreate(
            'account_group_product_visibility',
            ['accountGroup' => $accountGroup]
        );
        $accountGroupVisibility = (new AccountGroupProductVisibility())
            ->setProduct($product)
            ->setScope($scope)
            ->setVisibility(AccountGroupProductVisibility::CATEGORY);

        $this->em->persist($accountGroupVisibility);
    }

    /**
     * @param Account $account
     * @param Product $product
     */
    protected function createAccountProductVisibilityResolved(Account $account, Product $product)
    {
        $scope = $this->scopeManager->findOrCreate(
            'account_product_visibility',
            ['account' => $account]
        );
        $accountVisibility = (new AccountProductVisibility())
            ->setProduct($product)
            ->setScope($scope)
            ->setVisibility(AccountProductVisibility::CATEGORY);

        $this->em->persist($accountVisibility);
    }
}
