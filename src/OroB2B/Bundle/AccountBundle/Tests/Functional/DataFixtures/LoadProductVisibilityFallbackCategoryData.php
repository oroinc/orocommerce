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
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

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
     * @var Website
     */
    protected $website;

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
        $productVisibility = (new ProductVisibility())
            ->setProduct($product)
            ->setWebsite($this->website)
            ->setVisibility(ProductVisibility::CATEGORY);

        $this->em->persist($productVisibility);
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Product $product
     */
    protected function createAccountGroupProductVisibilityResolved(AccountGroup $accountGroup, Product $product)
    {
        $accountGroupVisibility = (new AccountGroupProductVisibility())
            ->setProduct($product)
            ->setAccountGroup($accountGroup)
            ->setWebsite($this->website)
            ->setVisibility(AccountGroupProductVisibility::CATEGORY);

        $this->em->persist($accountGroupVisibility);
    }

    /**
     * @param Account $account
     * @param Product $product
     */
    protected function createAccountProductVisibilityResolved(Account $account, Product $product)
    {
        $accountVisibility = (new AccountProductVisibility())
            ->setProduct($product)
            ->setAccount($account)
            ->setWebsite($this->website)
            ->setVisibility(AccountProductVisibility::CATEGORY);

        $this->em->persist($accountVisibility);
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
