<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadCategoryDemoData;

class LoadCategoryVisibilityDemoData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /** @var ContainerInterface */
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
            'OroB2B\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadCategoryDemoData',
            __NAMESPACE__ . '\LoadAccountDemoData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BAccountBundle/Migrations/Data/Demo/ORM/data/categories-visibility.csv');
        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $category = $this->getCategory($row['category']);
            $visibility = $row['visibility'];

            if ($row['all']) {
                $categoryVisibility = $this->createCategoryVisibility($category, $visibility);
                $manager->persist($categoryVisibility);
            }

            if ($row['account']) {
                $accountCategoryVisibility = $this->createAccountCategoryVisibility(
                    $category,
                    $this->getAccount($row['account']),
                    $visibility
                );
                $manager->persist($accountCategoryVisibility);
            }

            if ($row['accountGroup']) {
                $accountGroupCategoryVisibility = $this->createAccountGroupCategoryVisibility(
                    $category,
                    $this->getAccountGroup($row['accountGroup']),
                    $visibility
                );
                $manager->persist($accountGroupCategoryVisibility);
            }
        }

        fclose($handler);
        $manager->flush();
    }

    /**
     * @param string $name
     * @return Category
     */
    protected function getCategory($name)
    {
        return $this->getReference(LoadCategoryDemoData::CATEGORY_REFERENCE_PREFIX . $name);
    }

    /**
     * @param string $name
     * @return Account
     */
    protected function getAccount($name)
    {
        return $this->getReference(LoadAccountDemoData::ACCOUNT_REFERENCE_PREFIX . $name);
    }

    /**
     * @param string $name
     * @return AccountGroup
     */
    protected function getAccountGroup($name)
    {
        return $this->getReference(LoadAccountGroupDemoData::ACCOUNT_GROUP_REFERENCE_PREFIX . $name);
    }

    /**
     * @param Category $category
     * @param string $visibility
     * @return CategoryVisibility
     */
    protected function createCategoryVisibility(Category $category, $visibility)
    {
        $categoryVisibility = new CategoryVisibility();
        $categoryVisibility
            ->setCategory($category)
            ->setVisibility($visibility);

        return $categoryVisibility;
    }

    /**
     * @param Category $category
     * @param Account $account
     * @param string $visibility
     * @return AccountCategoryVisibility
     */
    protected function createAccountCategoryVisibility(Category $category, Account $account, $visibility)
    {
        $accountCategoryVisibility = new AccountCategoryVisibility();
        $accountCategoryVisibility
            ->setCategory($category)
            ->setAccount($account)
            ->setVisibility($visibility);

        return $accountCategoryVisibility;
    }

    /**
     * @param Category $category
     * @param AccountGroup $accountGroup
     * @param string $visibility
     * @return AccountGroupCategoryVisibility
     */
    protected function createAccountGroupCategoryVisibility(Category $category, AccountGroup $accountGroup, $visibility)
    {
        $accountGroupCategoryVisibility = new AccountGroupCategoryVisibility();
        $accountGroupCategoryVisibility
            ->setCategory($category)
            ->setAccountGroup($accountGroup)
            ->setVisibility($visibility);

        return $accountGroupCategoryVisibility;
    }
}
