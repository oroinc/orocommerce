<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Symfony\Component\Yaml\Yaml;

class LoadCategoryVisibilityResolvedData extends AbstractFixture implements DependentFixtureInterface
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
            LoadCategoryVisibilityData::class
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

        $categoryVisibility = (new CategoryVisibilityResolved($category))
            ->setVisibility($data['visibility']);

//        $this->setReference($data['reference'], $categoryVisibility);

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
            $accountGroupCategoryVisibility = (new AccountGroupCategoryVisibilityResolved($category, $accountGroup))
                ->setVisibility($data['visibility']);

//            $this->setReference($data['reference'], $accountGroupCategoryVisibility);

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
            $accountCategoryVisibility = (new AccountCategoryVisibilityResolved($category, $account))
                ->setVisibility($data['visibility']);

//            $this->setReference($data['reference'], $accountCategoryVisibility);

            $this->em->persist($accountCategoryVisibility);
        }
    }

    /**
     * @return array
     */
    protected function getCategoryVisibilityData()
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'category_visibilities_resolved.yml';

        return Yaml::parse(file_get_contents($filePath));
    }
}
