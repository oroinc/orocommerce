<?php

namespace Oro\Bundle\VisibilityBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountDemoData;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
            'Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadCategoryDemoData',
            LoadAccountDemoData::class
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroAccountBundle/Migrations/Data/Demo/ORM/data/categories-visibility.csv');
        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            $category = $this->getCategory($manager, $row['category']);
            $visibility = $row['visibility'];
            if ($row['all']) {
                $categoryVisibility = $this->createCategoryVisibility($category, $visibility);
                $manager->persist($categoryVisibility);
            }
            if ($row['account']) {
                $accountCategoryVisibility = $this->createAccountCategoryVisibility(
                    $category,
                    $this->getAccount($manager, $row['account']),
                    $visibility
                );
                $manager->persist($accountCategoryVisibility);
            }
            if ($row['accountGroup']) {
                $accountGroupCategoryVisibility = $this->createAccountGroupCategoryVisibility(
                    $category,
                    $this->getAccountGroup($manager, $row['accountGroup']),
                    $visibility
                );
                $manager->persist($accountGroupCategoryVisibility);
            }
        }
        fclose($handler);
        $manager->flush();
        $this->container->get('oro_account.visibility.cache.product.category.cache_builder')->buildCache();
    }
    /**
     * @param ObjectManager $manager
     * @param string $title
     * @return Category
     */
    protected function getCategory(ObjectManager $manager, $title)
    {
        return $manager->getRepository('OroCatalogBundle:Category')->findOneByDefaultTitle($title);
    }
    /**
     * @param ObjectManager $manager
     * @param string $name
     * @return Account
     */
    protected function getAccount(ObjectManager $manager, $name)
    {
        return $manager->getRepository('OroAccountBundle:Account')->findOneBy(['name' => $name]);
    }
    /**
     * @param ObjectManager $manager
     * @param string $name
     * @return AccountGroup
     */
    protected function getAccountGroup(ObjectManager $manager, $name)
    {
        return $manager->getRepository('OroAccountBundle:AccountGroup')->findOneBy(['name' => $name]);
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

