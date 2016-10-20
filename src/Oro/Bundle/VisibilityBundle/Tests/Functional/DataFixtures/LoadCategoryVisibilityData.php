<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class LoadCategoryVisibilityData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /** @var ObjectManager */
    protected $em;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

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

        $categories = $this->getCategoryVisibilityData();

        foreach ($categories as $categoryReference => $categoryVisibilityData) {
            /** @var Category $category */
            $category = $this->getReference($categoryReference);
            $this->createCategoryVisibilities($category, $categoryVisibilityData);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadAccounts::class,
            LoadGroups::class,
            LoadCategoryData::class,
        ];
    }

    /**
     * @param Category $category
     * @param array $categoryData
     */
    protected function createCategoryVisibilities(Category $category, array $categoryData)
    {
        $this->createCategoryVisibility($category, $categoryData['all']);

        $this->createAccountGroupCategoryVisibilities($category, $categoryData['groups']);

        $this->createAccountCategoryVisibilities($category, $categoryData['accounts']);
    }

    /**
     * @param Category $category
     * @param array $data
     */
    protected function createCategoryVisibility(Category $category, array $data)
    {
        if (!$data['visibility']) {
            return;
        }

        $scope = $this->scopeManager->findOrCreate('category_visibility', []);
        $categoryVisibility = (new CategoryVisibility())
            ->setCategory($category)
            ->setScope($scope)
            ->setVisibility($data['visibility']);

        $this->setReference($data['reference'], $categoryVisibility);

        $this->em->persist($categoryVisibility);
    }

    /**
     * @param Category $category
     * @param array $accountGroupVisibilityData
     */
    protected function createAccountGroupCategoryVisibilities(Category $category, array $accountGroupVisibilityData)
    {
        foreach ($accountGroupVisibilityData as $accountGroupReference => $data) {
            /** @var AccountGroup $accountGroup */
            $accountGroup = $this->getReference($accountGroupReference);
            $scope = $this->scopeManager->findOrCreate(
                'account_group_category_visibility',
                ['accountGroup' => $accountGroup]
            );

            $accountGroupCategoryVisibility = (new AccountGroupCategoryVisibility())
                ->setCategory($category)
                ->setScope($scope)
                ->setVisibility($data['visibility']);

            $this->setReference($data['reference'], $accountGroupCategoryVisibility);

            $this->em->persist($accountGroupCategoryVisibility);
        }
    }

    /**
     * @param Category $category
     * @param array $accountVisibilityData
     */
    protected function createAccountCategoryVisibilities(Category $category, array $accountVisibilityData)
    {
        foreach ($accountVisibilityData as $accountReference => $data) {
            /** @var Account $account */
            $account = $this->getReference($accountReference);
            $scope = $this->scopeManager->findOrCreate('account_category_visibility', ['account' => $account]);

            $accountCategoryVisibility = (new AccountCategoryVisibility())
                ->setCategory($category)
                ->setScope($scope)
                ->setVisibility($data['visibility']);

            $this->setReference($data['reference'], $accountCategoryVisibility);

            $this->em->persist($accountCategoryVisibility);
        }
    }

    /**
     * @return array
     */
    protected function getCategoryVisibilityData()
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'category_visibilities.yml';

        return Yaml::parse(file_get_contents($filePath));
    }
}
