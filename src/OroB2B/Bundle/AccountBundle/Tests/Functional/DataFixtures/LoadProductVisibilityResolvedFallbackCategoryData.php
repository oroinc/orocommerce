<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class LoadProductVisibilityResolvedFallbackCategoryData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    const VISIBILITY_RESOLVED_SOURCE = ProductVisibilityResolved::SOURCE_CATEGORY;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Website
     */
    protected $website;

    /**
     * @var array
     */
    protected $products = [
        'product.1',
        'product.2',
        'product.3',
        'product.4',
        'product.5',
        'product.6',
        'product.7',
    ];

    /**
     * @var array
     */
    protected static $productCategories = [
        'product.1' => LoadCategoryData::FIRST_LEVEL,
        'product.2' => LoadCategoryData::SECOND_LEVEL1,
        'product.5' => LoadCategoryData::SECOND_LEVEL2,
        'product.3' => LoadCategoryData::THIRD_LEVEL1,
        'product.4' => LoadCategoryData::THIRD_LEVEL2,
        'product.6' => LoadCategoryData::FOURTH_LEVEL1,
        'product.7' => LoadCategoryData::FOURTH_LEVEL2,
    ];

    /**
     * @var array
     */
    protected $hiddenResolved = [
        'product.4',
        'product.7',
    ];

    /**
     * @var array
     */
    protected $hiddenAccountGroupResolved = [
        'account_group.group2' => [
            'product.7',
        ],
        'account_group.group3' => [
            'product.3',
            'product.6',
        ],
    ];

    /**
     * @var array
     */
    protected $hiddenAccountResolved = [
        'account.level_1.1' => [
            'product.4',
            'product.7',
        ],
        'account.level_1.2' => [
            'product.7',
        ],
        'account.level_1.2.1' => [
            'product.7',
        ],
        'account.level_1.2.1.1' => [],
        'account.level_1.3.1' => [
            'product.3',
            'product.4',
            'product.7',
        ],
        'account.level_1.3.1.1' => [
            'product.2',
            'product.3',
            'product.6',
            'product.4',
        ],
        'account.level_1.4' => [
            'product.3',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\LoadCategoryVisibilityData',
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
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

        $this->website = $this->getWebsite();

        foreach ($this->products as $productReference) {
            /** @var Product $product */
            $product = $this->getReference($productReference);
            $this->createProductVisibilityResolved(
                $product,
                $this->resolveVisibility($productReference, $this->hiddenResolved)
            );
        }

        foreach ($this->hiddenAccountGroupResolved as $accountGroupReference => $hiddenProducts) {
            /** @var AccountGroup $accountGroup */
            $accountGroup = $this->getReference($accountGroupReference);
            foreach ($this->products as $productReference) {
                /** @var Product $product */
                $product = $this->getReference($productReference);
                $this->createAccountGroupProductVisibilityResolved(
                    $accountGroup,
                    $product,
                    $this->resolveVisibility($productReference, $hiddenProducts)
                );
            }
        }

        foreach ($this->hiddenAccountResolved as $accountReference => $hiddenProducts) {
            /** @var Account $account */
            $account = $this->getReference($accountReference);
            foreach ($this->products as $productReference) {
                /** @var Product $product */
                $product = $this->getReference($productReference);
                $this->createAccountProductVisibilityResolved(
                    $account,
                    $product,
                    $this->resolveVisibility($productReference, $hiddenProducts)
                );
            }
        }

        $this->em->flush();
    }

    /**
     * @param string $productReference
     * @param array $hiddenProductsData
     * @return integer
     */
    protected function resolveVisibility($productReference, array $hiddenProductsData)
    {
        if (!in_array($productReference, $hiddenProductsData)) {
            return ProductVisibilityResolved::VISIBILITY_VISIBLE;
        }
        return ProductVisibilityResolved::VISIBILITY_HIDDEN;
    }

    /**
     * @param Product $product
     * @param integer $visibility
     */
    protected function createProductVisibilityResolved(Product $product, $visibility)
    {
        $productVisibility = (new ProductVisibility())
            ->setProduct($product)
            ->setWebsite($this->website)
            ->setVisibility($this->getVisibilityByResolved($visibility));

        $this->em->persist($productVisibility);

        $resolvedVisibility = (new ProductVisibilityResolved($this->website, $product))
            ->setVisibility($visibility)
            ->setSource(self::VISIBILITY_RESOLVED_SOURCE)
            ->setSourceProductVisibility($productVisibility)
            ->setCategoryId($this->getCategoryByProduct($product)->getId());

        $this->em->persist($resolvedVisibility);
    }

    /**
     * @param integer $visibility
     * @return string
     */
    protected function getVisibilityByResolved($visibility)
    {
        return $visibility === 1 ? ProductVisibility::VISIBLE : ProductVisibility::HIDDEN;
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Product $product
     * @param integer $visibility
     */
    protected function createAccountGroupProductVisibilityResolved(
        AccountGroup $accountGroup,
        Product $product,
        $visibility
    ) {
        $accountGroupVisibility = (new AccountGroupProductVisibility())
            ->setProduct($product)
            ->setAccountGroup($accountGroup)
            ->setWebsite($this->website)
            ->setVisibility($this->getAccountGroupVisibilityByResolved($visibility));

        $this->em->persist($accountGroupVisibility);

        $resolvedVisibility = (new AccountGroupProductVisibilityResolved($this->website, $product, $accountGroup))
            ->setVisibility($visibility)
            ->setSource(self::VISIBILITY_RESOLVED_SOURCE)
            ->setSourceProductVisibility($accountGroupVisibility)
            ->setCategoryId($this->getCategoryByProduct($product)->getId());

        $this->em->persist($resolvedVisibility);
    }

    /**
     * @param integer $visibility
     * @return string
     */
    protected function getAccountGroupVisibilityByResolved($visibility)
    {
        return $visibility === 1 ? AccountGroupProductVisibility::VISIBLE : AccountGroupProductVisibility::HIDDEN;
    }

    /**
     * @param Account $account
     * @param Product $product
     * @param integer $visibility
     */
    protected function createAccountProductVisibilityResolved(
        Account $account,
        Product $product,
        $visibility
    ) {
        $accountVisibility = (new AccountProductVisibility())
            ->setProduct($product)
            ->setAccount($account)
            ->setWebsite($this->website)
            ->setVisibility($this->getAccountVisibilityByResolved($visibility));

        $this->em->persist($accountVisibility);

        $resolvedVisibility = (new AccountProductVisibilityResolved($this->website, $product, $account))
            ->setVisibility($visibility)
            ->setSource(self::VISIBILITY_RESOLVED_SOURCE)
            ->setSourceProductVisibility($accountVisibility)
            ->setCategoryId($this->getCategoryByProduct($product)->getId());

        $this->em->persist($resolvedVisibility);
    }

    /**
     * @param integer $visibility
     * @return string
     */
    protected function getAccountVisibilityByResolved($visibility)
    {
        return $visibility === 1 ? AccountProductVisibility::VISIBLE : AccountProductVisibility::HIDDEN;
    }

    /**
     * @param Product $product
     * @return Category
     */
    protected function getCategoryByProduct(Product $product)
    {
        return $this->getReference(self::$productCategories[$product->getSku()]);
    }

    /**
     * @return Website
     */
    protected function getWebsite()
    {
        return $this->container->get('doctrine')->getManagerForClass('OroB2BWebsiteBundle:Website')
            ->getRepository('OroB2BWebsiteBundle:Website')
            ->findOneBy(['name' => 'Default']);
    }
}
