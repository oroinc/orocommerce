<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

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

class LoadProductVisibilityData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
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
        /** @var Website $website */
        $website = $this->getReference($data['website']);

        $productVisibility = (new ProductVisibility())
            ->setProduct($product)
            ->setWebsite($website)
            ->setVisibility($data['all']);

        $manager->persist($productVisibility);

        $this->setReference($this->getProductVisibilityReference($productVisibility), $productVisibility);

        $this->createAccountGroupVisibilities($manager, $product, $website, $data['groups']);

        $this->createAccountVisibilities($manager, $product, $website, $data['accounts']);
    }

    /**
     * @param ProductVisibility $productVisibility
     * @return string
     */
    protected function getProductVisibilityReference(ProductVisibility $productVisibility)
    {
        return $productVisibility->getProduct()->getSku() . '.visibility.all';
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
        foreach ($accountGroupsData as $groupReference => $visibility) {
            /** @var AccountGroup $accountGroup */
            $accountGroup = $this->getReference($groupReference);

            $accountGroupProductVisibility = (new AccountGroupProductVisibility())
                ->setProduct($product)
                ->setWebsite($website)
                ->setAccountGroup($accountGroup)
                ->setVisibility($visibility)
            ;

            $manager->persist($accountGroupProductVisibility);

            $this->setReference(
                $this->getAccountGroupProductVisibilityReference($accountGroupProductVisibility),
                $accountGroupProductVisibility
            );
        }
    }

    /**
     * @param AccountGroupProductVisibility $entity
     * @return string
     */
    protected function getAccountGroupProductVisibilityReference(AccountGroupProductVisibility $entity)
    {
        return $entity->getProduct() . '.visibility.' . $entity->getAccountGroup()->getName();
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
        foreach ($accountsData as $accountReference => $visibility) {
            /** @var Account $account */
            $account = $this->getReference($accountReference);

            $accountProductVisibility = (new AccountProductVisibility())
                ->setProduct($product)
                ->setWebsite($website)
                ->setAccount($account)
                ->setVisibility($visibility)
            ;

            $manager->persist($accountProductVisibility);

            $this->setReference(
                $this->getAccountProductVisibilityReference($accountProductVisibility),
                $accountProductVisibility
            );
        }
    }

    /**
     * @param AccountProductVisibility $entity
     * @return string
     */
    protected function getAccountProductVisibilityReference(AccountProductVisibility $entity)
    {
        return $entity->getProduct() . '.visibility.' . $entity->getAccount()->getName();
    }

    /**
     * @return array
     */
    protected function getProductVisibilities()
    {
        $fixturesFileName = __DIR__ . '/data/product_visibilities.yml';

        return Yaml::parse($fixturesFileName);
    }
}
