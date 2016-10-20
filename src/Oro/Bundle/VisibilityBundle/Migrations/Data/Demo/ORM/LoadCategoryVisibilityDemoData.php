<?php

namespace Oro\Bundle\VisibilityBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadScopeAccountDemoData;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;

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
            LoadScopeAccountDemoData::class
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroVisibilityBundle/Migrations/Data/Demo/ORM/data/categories-visibility.csv');
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
            if ($row['scopeAccount']) {
                $accountCategoryVisibility = $this->createAccountCategoryVisibility(
                    $category,
                    $this->getScopeAccount($manager, $row['scopeAccount']),
                    $visibility
                );
                $manager->persist($accountCategoryVisibility);
            }
            if ($row['scopeAccountGroup']) {
                $accountGroupCategoryVisibility = $this->createAccountGroupCategoryVisibility(
                    $category,
                    $this->getScopeAccountGroup($manager, $row['scopeAccountGroup']),
                    $visibility
                );
                $manager->persist($accountGroupCategoryVisibility);
            }
        }
        fclose($handler);
        $manager->flush();
//        todo BB 4506
//        $this->container->get('oro_visibility.visibility.cache.product.category.cache_builder')->buildCache();
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
     * @param int $id
     * @return Scope
     */
    protected function getScopeAccount(ObjectManager $manager, $id)
    {
        return $manager->getRepository('OroScopeBundle:Scope')->findOneBy(['id' => $id]);
    }
    /**
     * @param ObjectManager $manager
     * @param int $id
     * @return Scope
     */
    protected function getScopeAccountGroup(ObjectManager $manager, $id)
    {
        return $manager->getRepository('OroScopeBundle:Scope')->findOneBy(['id' => $id]);
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
     * @param Scope $scope
     * @param string $visibility
     * @return AccountCategoryVisibility
     */
    protected function createAccountCategoryVisibility(Category $category, Scope $scope, $visibility)
    {
        $accountCategoryVisibility = new AccountCategoryVisibility();
        $accountCategoryVisibility
            ->setCategory($category)
            ->setScope($scope)
            ->setVisibility($visibility);
        return $accountCategoryVisibility;
    }
    /**
     * @param Category $category
     * @param Scope $scope
     * @param string $visibility
     * @return AccountGroupCategoryVisibility
     */
    protected function createAccountGroupCategoryVisibility(Category $category, Scope $scope, $visibility)
    {
        $accountGroupCategoryVisibility = new AccountGroupCategoryVisibility();
        $accountGroupCategoryVisibility
            ->setCategory($category)
            ->setScope($scope)
            ->setVisibility($visibility);
        return $accountGroupCategoryVisibility;
    }
}
