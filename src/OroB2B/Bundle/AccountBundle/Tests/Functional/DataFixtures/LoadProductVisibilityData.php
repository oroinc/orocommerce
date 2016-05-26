<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

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
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
            'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        // set default fallback to categories
        $configVisibilities = $manager->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility')
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
            return $this->container
                ->get('doctrine')
                ->getManagerForClass('OroB2BWebsiteBundle:Website')
                ->getRepository('OroB2BWebsiteBundle:Website')->findOneBy(['name' => $websiteName]);
        }

        return $this->getReference($websiteName);
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
            $accountGroup = $this->getReference($groupReference);

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
