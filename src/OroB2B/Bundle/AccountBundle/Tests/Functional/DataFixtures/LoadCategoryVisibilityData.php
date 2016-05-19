<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Symfony\Component\Yaml\Yaml;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class LoadCategoryVisibilityData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var ObjectManager */
    protected $em;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->em = $manager;

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
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
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

        $categoryVisibility = (new CategoryVisibility())
            ->setCategory($category)
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
            $accountGroupCategoryVisibility = (new AccountGroupCategoryVisibility())
                ->setCategory($category)
                ->setAccountGroup($accountGroup)
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
            $accountCategoryVisibility = (new AccountCategoryVisibility())
                ->setCategory($category)
                ->setAccount($account)
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
