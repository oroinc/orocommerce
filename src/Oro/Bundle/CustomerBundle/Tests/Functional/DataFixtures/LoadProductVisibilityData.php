<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\CustomerBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\CustomerBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\LoadAnonymousAccountGroup;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadProductVisibilityData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

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
    public function getDependencies()
    {
        return [
            LoadGroups::class,
            LoadAccounts::class,
            LoadCategoryProductData::class,
            LoadWebsiteData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        // set default fallback to categories
        $configVisibilities = $manager->getRepository('OroCustomerBundle:Visibility\ProductVisibility')
            ->findBy(['visibility' => ProductVisibility::CONFIG]);
        foreach ($configVisibilities as $visibilityEntity) {
            $visibilityEntity->setVisibility(ProductVisibility::CATEGORY);
        }
        $manager->flush();

        // load visibilities
        foreach ($this->getProductVisibilities() as $productReference => $productVisibilityData) {
            /** @var Product $product */
            $product = $this->getReference($productReference);
            $this->createProductVisibilities($manager, $product, $productVisibilityData);
        }
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param Product $product
     * @param array $data
     */
    protected function createProductVisibilities(ObjectManager $manager, Product $product, array $data)
    {
        $website = $this->getWebsite($data['website']);

        $productVisibility = (new ProductVisibility())
            ->setProduct($product)
            ->setWebsite($website)
            ->setVisibility($data['all']['visibility']);

        $manager->persist($productVisibility);

        $this->setReference($data['all']['reference'], $productVisibility);

        $this->createAccountGroupVisibilities($manager, $product, $website, $data['groups']);

        $this->createAccountVisibilities($manager, $product, $website, $data['accounts']);
    }

    /**
     * @param string $websiteName
     * @return Website
     */
    protected function getWebsite($websiteName)
    {
        if ($websiteName === 'Default') {
            $website = $this->container
                ->get('doctrine')
                ->getManagerForClass('OroWebsiteBundle:Website')
                ->getRepository('OroWebsiteBundle:Website')->findOneBy(['name' => $websiteName]);
        } else {
            /** @var Website $website */
            $website = $this->getReference($websiteName);
        }

        return $website;
    }

    /**
     * @param string $groupReference
     * @return AccountGroup
     */
    private function getAccountGroup($groupReference)
    {
        if ($groupReference === 'account_group.anonymous') {
            $accountGroup = $this->container
                ->get('doctrine')
                ->getManagerForClass('OroCustomerBundle:AccountGroup')
                ->getRepository('OroCustomerBundle:AccountGroup')
                ->findOneBy(['name' => LoadAnonymousAccountGroup::GROUP_NAME_NON_AUTHENTICATED]);
        } else {
            /** @var AccountGroup $accountGroup */
            $accountGroup = $this->getReference($groupReference);
        }

        return $accountGroup;
    }

    /**
     * @param ObjectManager $manager
     * @param Product $product
     * @param Website $website
     * @param array $accountGroupsData
     */
    protected function createAccountGroupVisibilities(
        ObjectManager $manager,
        Product $product,
        Website $website,
        array $accountGroupsData
    ) {
        foreach ($accountGroupsData as $groupReference => $accountGroupData) {
            /** @var AccountGroup $accountGroup */
            $accountGroup = $this->getAccountGroup($groupReference);

            $accountGroupProductVisibility = (new AccountGroupProductVisibility())
                ->setProduct($product)
                ->setWebsite($website)
                ->setAccountGroup($accountGroup)
                ->setVisibility($accountGroupData['visibility'])
            ;

            $manager->persist($accountGroupProductVisibility);

            $this->setReference($accountGroupData['reference'], $accountGroupProductVisibility);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param Product $product
     * @param Website $website
     * @param array $accountsData
     */
    protected function createAccountVisibilities(
        ObjectManager $manager,
        Product $product,
        Website $website,
        array $accountsData
    ) {
        foreach ($accountsData as $accountReference => $accountData) {
            /** @var Account $account */
            $account = $this->getReference($accountReference);

            $accountProductVisibility = (new AccountProductVisibility())
                ->setProduct($product)
                ->setWebsite($website)
                ->setAccount($account)
                ->setVisibility($accountData['visibility'])
            ;

            $manager->persist($accountProductVisibility);

            $this->setReference($accountData['reference'], $accountProductVisibility);
        }
    }

    /**
     * @return array
     */
    protected function getProductVisibilities()
    {
        $fixturesFileName = __DIR__ . '/data/product_visibilities.yml';

        return Yaml::parse(file_get_contents($fixturesFileName));
    }
}

